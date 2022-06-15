<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ChakraWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cwallet {count=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creation of chakra wallet';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $baseUrl = env('BASE_URL');
            $count = $this->argument('count');
            for ($i=0; $i < $count ; $i++) { 
                $pin = generateRandomString();
                list($request, $data) = walletCreationChakra($baseUrl, $pin);
                storeWalletChakra($data, $request, $pin);
            }
        } catch (\Exception $e) {
            //throw $th;
        }
        
    }
}
