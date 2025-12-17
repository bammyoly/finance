<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Jobs\MatchOrderJob;
use App\Models\Asset;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'symbol' => ['required', Rule::in(['BTC','ETH'])],
            'side'   => ['required', Rule::in(['buy','sell'])],
            'price'  => ['required','numeric','gt:0'],
            'amount' => ['required','numeric','gt:0'],
        ]);

        $user = $request->user();

        $order = DB::transaction(function () use ($data, $user) {
            $symbol = $data['symbol'];
            $side   = $data['side'];
            $price  = (string) $data['price'];
            $amount = (string) $data['amount'];

            if ($side === 'buy') {
                $lockedUsd = bcmul($amount, $price, 2);

                $u = \App\Models\User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                if (bccomp((string)$u->balance, $lockedUsd, 2) < 0) {
                    abort(422, 'Insufficient USD balance.');
                }

                $u->balance = bcsub((string)$u->balance, $lockedUsd, 2);
                $u->save();

                return Order::create([
                    'user_id' => $u->id,
                    'symbol' => $symbol,
                    'side' => 'buy',
                    'price' => $price,
                    'amount' => $amount,
                    'status' => Order::OPEN,
                    'locked_usd' => $lockedUsd,
                ]);
            }

            // SELL
            $asset = Asset::query()
                ->where('user_id', $user->id)
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            if (!$asset) {
                abort(422, 'No asset balance for this symbol.');
            }

            if (bccomp((string)$asset->amount, $amount, 18) < 0) {
                abort(422, 'Insufficient asset balance.');
            }

            $asset->amount = bcsub((string)$asset->amount, $amount, 18);
            $asset->locked_amount = bcadd((string)$asset->locked_amount, $amount, 18);
            $asset->save();

            return Order::create([
                'user_id' => $user->id,
                'symbol' => $symbol,
                'side' => 'sell',
                'price' => $price,
                'amount' => $amount,
                'status' => Order::OPEN,
                'locked_usd' => null,
            ]);
        });

        MatchOrderJob::dispatch($order->id);

        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function orderbook(Request $request)
    {
        $data = $request->validate([
            'symbol' => ['required', Rule::in(['BTC','ETH'])],
        ]);

        $symbol = $data['symbol'];

        $buys = Order::query()
            ->where('symbol', $symbol)->where('status', Order::OPEN)->where('side', 'buy')
            ->orderByDesc('price')->orderBy('created_at')
            ->get();

        $sells = Order::query()
            ->where('symbol', $symbol)->where('status', Order::OPEN)->where('side', 'sell')
            ->orderBy('price')->orderBy('created_at')
            ->get();

        return response()->json([
            'buys' => $buys,
            'sells' => $sells,
        ]);
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($order) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== Order::OPEN) {
                abort(422, 'Only open orders can be cancelled.');
            }

            if ($order->side === 'buy') {
                $u = \App\Models\User::query()->whereKey($order->user_id)->lockForUpdate()->firstOrFail();
                $u->balance = bcadd((string)$u->balance, (string)$order->locked_usd, 2);
                $u->save();
            } else {
                $asset = \App\Models\Asset::query()
                    ->where('user_id', $order->user_id)->where('symbol', $order->symbol)
                    ->lockForUpdate()->firstOrFail();

                $asset->locked_amount = bcsub((string)$asset->locked_amount, (string)$order->amount, 18);
                $asset->amount = bcadd((string)$asset->amount, (string)$order->amount, 18);
                $asset->save();
            }

            $order->status = Order::CANCELLED;
            $order->save();
        });

        return response()->json(['ok' => true]);
    }

    public function myOrders(Request $request)
    {
        return \App\Models\Order::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();
    }

}
