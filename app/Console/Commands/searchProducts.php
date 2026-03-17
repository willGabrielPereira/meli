<?php

namespace App\Console\Commands;

use App\Helpers\Meli;
use App\Helpers\Tray;
use App\Jobs\ProcessProduct;
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
                return Command::FAILURE;
            }
        }

        $meli = new Meli($token);
        $paging = (object) ['total' => 0, 'limit' => 50, 'offset' => 0];

        do {
            $productsResponse = $meli->searchProducts($seller, $paging->offset);

            if (!isset($productsResponse->results) || empty($productsResponse->results)) {
                break; // Sem mais resultados ou falha
            }

            $products = $productsResponse->results;

            foreach ($products as $productId) {
                ProcessProduct::dispatch($productId, $seller, $token);
            }

            $paging = $productsResponse->paging;

            $this->info("Foram enviados " . count($products) . " produtos para a fila (Offset atual: {$paging->offset}).");

            $paging->offset += $paging->limit;
        } while ($paging->offset < $paging->total);
    }
}
