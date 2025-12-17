<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchingEngine
{
    private const FEE_RATE = '0.015'; // 1.5%

    public function matchOrder(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            /** @var Order $order */
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();

            if ($order->status !== Order::OPEN) {
                return;
            }

            $counter = $this->findFirstValidCounter($order);
            if (!$counter) return;

            // Full match only (strict)
            if (bccomp($counter->amount, $order->amount, 18) !== 0) {
                return;
            }

            // Determine sides
            $buy  = $order->side === 'buy' ? $order : $counter;
            $sell = $order->side === 'sell' ? $order : $counter;

            // Use maker price = counter price (simple and consistent)
            $execPrice = (string) $counter->price;
            $amount    = (string) $order->amount;

            $usdVolume = $this->mul($amount, $execPrice, 2);          // amount * price
            $feeUsd    = $this->mul($usdVolume, self::FEE_RATE, 2);   // volume * 1.5%
            $sellerReceives = $this->sub($usdVolume, $feeUsd, 2);

            // Lock required rows
            $buyer  = User::query()->whereKey($buy->user_id)->lockForUpdate()->firstOrFail();
            $seller = User::query()->whereKey($sell->user_id)->lockForUpdate()->firstOrFail();

            $buyerAsset = Asset::query()
                ->where('user_id', $buyer->id)->where('symbol', $buy->symbol)
                ->lockForUpdate()->first();

            if (!$buyerAsset) {
                $buyerAsset = Asset::create([
                    'user_id' => $buyer->id,
                    'symbol' => $buy->symbol,
                    'amount' => 0,
                    'locked_amount' => 0,
                ]);
                $buyerAsset->refresh();
                $buyerAsset = Asset::query()->whereKey($buyerAsset->id)->lockForUpdate()->firstOrFail();
            }

            $sellerAsset = Asset::query()
                ->where('user_id', $seller->id)->where('symbol', $sell->symbol)
                ->lockForUpdate()->firstOrFail();

            // Buyer must have locked_usd >= usdVolume (locked at placement: amount * buy.price)
            if ($buy->locked_usd === null || bccomp((string)$buy->locked_usd, $usdVolume, 2) < 0) {
                return;
            }

            // Seller must have locked_amount >= amount
            if (bccomp((string)$sellerAsset->locked_amount, $amount, 18) < 0) {
                return;
            }

            // --- Settlement ---
            // Buyer: unlock/refund leftover locked USD
            $refund = $this->sub((string)$buy->locked_usd, $usdVolume, 2);
            $buyer->balance = $this->add((string)$buyer->balance, $refund, 2);
            $buyer->save();

            // Buyer: receive asset
            $buyerAsset->amount = $this->add((string)$buyerAsset->amount, $amount, 18);
            $buyerAsset->save();

            // Seller: release locked asset
            $sellerAsset->locked_amount = $this->sub((string)$sellerAsset->locked_amount, $amount, 18);
            $sellerAsset->save();

            // Seller: receive USD minus fee
            $seller->balance = $this->add((string)$seller->balance, $sellerReceives, 2);
            $seller->save();

            // Mark orders filled
            $buy->status = Order::FILLED;
            $sell->status = Order::FILLED;
            $buy->save();
            $sell->save();

            // Broadcast AFTER COMMIT (prevents UI seeing uncommitted data)
            DB::afterCommit(function () use ($buyer, $seller, $buy, $sell, $execPrice, $amount, $usdVolume, $feeUsd) {
                $buyerFresh = User::with('assets')->findOrFail($buyer->id);
                $sellerFresh = User::with('assets')->findOrFail($seller->id);

                event(new OrderMatched(
                    buyerId: $buyerFresh->id,
                    sellerId: $sellerFresh->id,
                    payload: [
                        'trade' => [
                            'symbol' => $buy->symbol,
                            'price' => $execPrice,
                            'amount' => $amount,
                            'usd_volume' => $usdVolume,
                            'fee_usd' => $feeUsd,
                        ],
                        'orders' => [
                            'buy_order_id' => $buy->id,
                            'sell_order_id' => $sell->id,
                            'buy_status' => $buy->status,
                            'sell_status' => $sell->status,
                        ],
                        'wallets' => [
                            'buyer' => [
                                'usd_balance' => (string)$buyerFresh->balance,
                                'assets' => $buyerFresh->assets->map(fn($a) => [
                                    'symbol' => $a->symbol,
                                    'amount' => (string)$a->amount,
                                    'locked_amount' => (string)$a->locked_amount,
                                ])->values(),
                            ],
                            'seller' => [
                                'usd_balance' => (string)$sellerFresh->balance,
                                'assets' => $sellerFresh->assets->map(fn($a) => [
                                    'symbol' => $a->symbol,
                                    'amount' => (string)$a->amount,
                                    'locked_amount' => (string)$a->locked_amount,
                                ])->values(),
                            ],
                        ],
                    ]
                ));
            });
        });
    }

    private function findFirstValidCounter(Order $order): ?Order
    {
        $query = Order::query()
            ->where('symbol', $order->symbol)
            ->where('status', Order::OPEN)
            ->where('side', $order->side === 'buy' ? 'sell' : 'buy');

        if ($order->side === 'buy') {
            // new BUY matches first SELL where sell.price <= buy.price (cheapest first)
            $query->where('price', '<=', $order->price)->orderBy('price')->orderBy('created_at');
        } else {
            // new SELL matches first BUY where buy.price >= sell.price (highest first)
            $query->where('price', '>=', $order->price)->orderByDesc('price')->orderBy('created_at');
        }

        return $query->lockForUpdate()->first();
    }

    // --- BCMath helpers (require ext-bcmath) ---
    private function add(string $a, string $b, int $scale): string { return bcadd($a, $b, $scale); }
    private function sub(string $a, string $b, int $scale): string { return bcsub($a, $b, $scale); }
    private function mul(string $a, string $b, int $scale): string { return bcmul($a, $b, $scale); }
}
