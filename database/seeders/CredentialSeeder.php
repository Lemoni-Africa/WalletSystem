<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Providers;

class CredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('merchant_creds')->insert([
            'merchantId' => env('MERCHANT_ID'),
            'apiKey' => '398289833be04b98832dca4272c6e929',
            'provider' =>  Providers::CHAKRA->value,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
