<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Tray;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TrayTest extends TestCase
{
    public function test_api_success(): void
    {
        Http::fake([
            '*' => Http::response(['data' => 'success'], 200),
        ]);

        $tray = new Tray();
        $response = $tray->api('/test-route');

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

        $tray = new Tray();
        $tray->api('/test-route');
    }

    public function test_api_handles_401(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'invalid_token'], 401),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with("Token inválido detectado na API da Tray", \Mockery::type('array'));

        $tray = new Tray();
        $response = $tray->api('/test-route');

        $this->assertNull($response);
    }

    public function test_api_handles_generic_failure(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Erro ao buscar dados do servidor");
        $this->expectExceptionCode(500);

        $tray = new Tray();
        $tray->api('/test-route');
    }

    public function test_get_seller_token_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'inactive_token' => false,
                'access_token' => 'valid-token-123'
            ], 200),
        ]);

        $tray = new Tray();
        $token = $tray->getSellerToken(10);

        $this->assertEquals('valid-token-123', $token);
    }

    public function test_get_seller_token_inactive(): void
    {
        Http::fake([
            '*' => Http::response([
                'inactive_token' => true,
                'access_token' => 'invalid-token'
            ], 200),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token inativo");
        $this->expectExceptionCode(422);

        $tray = new Tray();
        $tray->getSellerToken(10);
    }

    public function test_get_seller_data_without_seller(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("É necessário informar um vendedor");
        $this->expectExceptionCode(422);

        $tray = new Tray();
        $tray->getSellerData(null);
    }
}
