<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\User;

class TestWalletSeeder extends Seeder
{

    public function run(): void
    {
        $seller = User::where('email', 'olaboss300@gmail.com')->first();

        if ($seller) {
            Asset::updateOrCreate(
                ['user_id' => $seller->id, 'symbol' => 'BTC'],
                ['amount' => '1.000000000000000000', 'locked_amount' => '0']
            );
        }

        $buyer = User::where('email', 'sleekatom@gmsil.com')->first();

        if ($buyer) {
            $buyer->balance = '100000.00';
            $buyer->save();
        }
    }
}
