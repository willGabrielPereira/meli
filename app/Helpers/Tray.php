<?php

namespace App\Helpers;

use \Illuminate\Support\Facades\Http;

class Tray
{
    public function api(string $route, string $method = 'GET', array $params = [])
    {
        $baseUrl = env('TRAY_URL', 'http://mockoon:3001/traymeli');
        $url = rtrim($baseUrl, '/') . '/' . ltrim($route, '/');

        $options = [];
        if (!empty($params)) {
            $options[strtoupper($method) === 'GET' ? 'query' : 'json'] = $params;
        }

        $response = Http::accept('application/json')->send($method, $url, $options);

        if ($response->status() === 429) {
            // Lança uma exceção com o código 429, deixando a responsabilidade
            // do retry para quem estiver consumindo o método.
            throw new \Exception("Too Many Requests", 429);
        }

        // Se o token for inválido, registra um Log e prossegue sem interromper o código 
        if ($response->status() === 401) {
            \Illuminate\Support\Facades\Log::warning("Token inválido detectado na API da Tray", [
                'route'    => $route,
                'response' => $response->json()
            ]);

            return null;
        }

        if ($response->failed()) {
            throw new \Exception("Erro ao buscar dados do servidor", $response->status());
        }

        return $response->object();
    }

    public function getSellerData(?int $seller)
    {
        if (!$seller) {
            throw new \Exception("É necessário informar um vendedor", 422);
        }

        $url = '/sellers/' . $seller;
        return $this->api($url);
    }

    public function getSellerToken(?int $seller)
    {
        if (!$seller) {
            throw new \Exception("É necessário informar um vendedor", 422);
        }

        $sellerData = $this->getSellerData($seller);
        if ($sellerData->inactive_token) {
            throw new \Exception("Token inativo", 422);
        }

        return $sellerData->access_token;
    }
}
