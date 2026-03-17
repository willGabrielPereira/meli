<?php

namespace App\Jobs;

use App\Helpers\Meli;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $productId;
    public $seller;
    public $token;

    public function __construct($productId, $seller, $token)
    {
        $this->productId = $productId;
        $this->seller = $seller;
        $this->token = $token;
    }

    public function handle(): void
    {
        Log::info('Processando produto do MELI', ['product_id' => $this->productId]);

        $meli = new Meli($this->token);
        $product = $meli->getProduct($this->productId);

        $product = Product::seller($this->seller)->meliId($this->productId)->first();

        if ($product) {
            $product->update([
                'name' => $product->title,
                'price' => $product->price,
                'stock' => $product->stock,
            ]);
        } else {
            Product::create([
                'seller_id' => $this->seller,
                'meli_id' => $this->productId,
                'name' => $product->title,
                'price' => $product->price,
                'stock' => $product->stock,
            ]);
        }
    }
}
