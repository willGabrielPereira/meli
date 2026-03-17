<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Pega o limite da requisição (per_page, defaults to 15), limita a, no máximo, 50 para não sobrecarregar
        $limit = (int) $request->query('limit', 15);

        if ($limit > 50) {
            $limit = 50;
        } elseif ($limit < 1) {
            $limit = 15;
        }

        $query = Product::query();

        // Poderemos filtrar por seller se mandarem
        if ($request->has('seller')) {
            $query->seller((int) $request->query('seller'));
        }

        // Paginando os resultados (o Laravel automaticamente lidará com o "page")
        // Como o Laravel por default suporta "per_page", mas há um controle manual sobre "limit"
        $products = $query->paginate($limit);

        return response()->json($products);
    }
}
