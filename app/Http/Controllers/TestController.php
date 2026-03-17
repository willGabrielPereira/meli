<?php

namespace App\Http\Controllers;

use App\Helpers\Meli;
use App\Helpers\Tray;

class TestController extends Controller
{
    public function index()
    {
        $tray = new Tray();
        $sellerData = $tray->getSellerData(env('SELLER_ID'));
        dd($sellerData);
    }
}
