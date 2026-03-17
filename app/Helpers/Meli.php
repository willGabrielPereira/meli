<?php

namespace App\Helpers;

use \Illuminate\Support\Facades\Http;

class Meli
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function api(string $route, string $method = 'GET', array $params = [])
    {
        $baseUrl = env('MELI_URL', 'http://mockoon:3001/');
        $url = rtrim($baseUrl, '/') . '/' . ltrim($route, '/');

        $options = [];
        if (!empty($params)) {
            $options[strtoupper($method) === 'GET' ? 'query' : 'json'] = $params;
        }

        $response = Http::accept('application/json')->withToken($this->token)->send($method, $url, $options);

        if ($response->status() === 429) {
            // Lança uma exceção com o código 429, deixando a responsabilidade
            // do retry para quem estiver consumindo o método.
            throw new \Exception("Too Many Requests", 429);
        }

        // Se o token for inválido, registra um Log e prossegue sem interromper o código 
        if ($response->status() === 401) {
            \Illuminate\Support\Facades\Log::warning("Token inválido detectado na API do Mercado Livre", [
                'route'    => $route,
                'response' => $response->json()
            ]);

            return null;
        }

        if ($response->failed()) {
            throw new \Exception("Erro ao buscar dados do servidor", 1);
        }

        return $response->json();
    }

    public function searchProducts(int $seller)
    {
        return $this->api('/sites/MLB/search', 'GET', ['seller_id' => $seller]);
    }
}
