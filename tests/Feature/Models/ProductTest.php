<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_sync_is_updated_on_save(): void
    {
        Carbon::setTestNow(Carbon::now());

        $product = Product::create([
            'meli_id' => 'MLB123',
            'title' => 'Produto Teste',
            'seller' => 10,
            'status' => 'active',
        ]);

        $this->assertNotNull($product->last_sync);
        $this->assertEquals(Carbon::now()->format('Y-m-d H:i:s'), $product->last_sync->format('Y-m-d H:i:s'));
    }
}
