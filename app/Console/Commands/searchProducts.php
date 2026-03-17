<?php

namespace App\Console\Commands;

use App\Helpers\Meli;
use App\Helpers\Tray;
use Illuminate\Console\Command;

class searchProducts extends Command
{
    protected $signature = 'app:search-products {seller : ID do seller}';

    protected $description = 'Busca os produtos disponíveis no MELI com base do seller e insere no banco de dados';

    public function handle()
    {
        $seller = $this->argument('seller');

        $tray = new Tray();
        $token = null;

        for ($i = 1; $i <= 10; $i++) {
            try {
                $token = $tray->getSellerToken($seller);
                break;
            } catch (\Exception $e) {
                if ($e->getCode() === 429) {
                    $this->info('Muitas requisições, aguarde 3 segundos...');
                    sleep(3);
                    continue;
                }

                $this->error('Erro ao buscar token do seller: ' . $e->getMessage());
                continue;
            }
        }

        $meli = new Meli($token);
        $products = $meli->searchProducts($seller);

        dd($products);
    }
}
