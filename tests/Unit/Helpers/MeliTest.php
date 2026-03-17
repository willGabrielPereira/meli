<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Meli;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MeliTest extends TestCase
{
    public function test_api_success(): void
    {
        Http::fake([
            '*' => Http::response(['data' => 'success'], 200),
        ]);

        $meli = new Meli('valid-token');
        $response = $meli->api('/test-route');

        $this->assertEquals('success', $response->data);
    }

    public function test_api_handles_429(): void
    {
        Http::fake([
            '*' => Http::response([], 429),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Too Many Requests");
        $this->expectExceptionCode(429);

        $meli = new Meli('valid-token');
        $meli->api('/test-route');
    }

    public function test_api_handles_401(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'invalid_token'], 401),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with("Token inválido detectado na API do Mercado Livre", \Mockery::type('array'));

        $meli = new Meli('invalid-token');
        $response = $meli->api('/test-route');

        $this->assertNull($response);
    }

    public function test_api_handles_generic_failure(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Erro ao buscar dados do servidor");
        $this->expectExceptionCode(1);

        $meli = new Meli('valid-token');
        $meli->api('/test-route');
    }

    public function test_search_products(): void
    {
        Http::fake([
            '*' => Http::response(['results' => []], 200),
        ]);

        $meli = new Meli('valid-token');
        $meli->searchProducts(12345, 50);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_contains($request->url(), '/sites/MLB/search') &&
                   $request['seller_id'] === 12345 &&
                   $request['offset'] === 50;
        });
    }

    public function test_get_product(): void
    {
        Http::fake([
            '*' => Http::response(['id' => 'MLB123456'], 200),
        ]);

        $meli = new Meli('valid-token');
        $meli->getProduct('MLB123456');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_contains($request->url(), '/items/MLB123456');
        });
    }
}
