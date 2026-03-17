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

    public function __construct(string $productId, int $seller, string $token)
    {
        $this->productId = $productId;
        $this->seller = $seller;
        $this->token = $token;
    }

    public function handle(): void
    {
        Log::info('Processando produto do MELI', ['product_id' => $this->productId, 'seller' => $this->seller, 'token' => $this->token]);

        $meli = new Meli($this->token);
        $product = $meli->getProduct($this->productId);

        $savedProduct = Product::seller($this->seller)->meliId($this->productId)->first();

        if ($savedProduct) {
            $savedProduct->update([
                'title' => $product->title,
                'status' => $product->status,
            ]);
        } else {
            Product::create([
                'seller' => $this->seller,
                'meli_id' => $this->productId,
                'title' => $product->title,
                'status' => $product->status,
            ]);
        }
    }
}
