<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessProduct;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProcessProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_creates_new_product_when_not_exists(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'MLB12345',
                'title' => 'Produto Teste Novo',
                'status' => 'active'
            ], 200),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Processando produto do MELI', \Mockery::type('array'));

        $job = new ProcessProduct('MLB12345', 10, 'valid-token');
        $job->handle();

        $this->assertDatabaseHas('products', [
            'meli_id' => 'MLB12345',
            'seller' => 10,
            'title' => 'Produto Teste Novo',
            'status' => 'active',
        ]);
    }

    public function test_job_updates_existing_product(): void
    {
        Product::create([
            'meli_id' => 'MLB54321',
            'seller' => 20,
            'title' => 'Produto Antigo',
            'status' => 'inactive',
        ]);

        Http::fake([
            '*' => Http::response([
                'id' => 'MLB54321',
                'title' => 'Produto Atualizado',
                'status' => 'active'
            ], 200),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Processando produto do MELI', \Mockery::type('array'));

        $job = new ProcessProduct('MLB54321', 20, 'valid-token');
        $job->handle();

        $this->assertDatabaseHas('products', [
            'meli_id' => 'MLB54321',
            'seller' => 20,
            'title' => 'Produto Atualizado',
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('products', [
            'meli_id' => 'MLB54321',
            'title' => 'Produto Antigo',
            'status' => 'inactive',
        ]);
    }
}
