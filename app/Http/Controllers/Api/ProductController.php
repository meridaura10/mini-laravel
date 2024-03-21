<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Framework\Kernel\Http\Requests\Request;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\Response;

class ProductController extends Controller
{
    public function index(): ResponseInterface
    {
        $productFirst = Product::get();

        $productCreate = Product::create(['title' => 'new product title']);

        dd($productCreate, $productFirst);

        // $product2 = $product1->update(['title' => 'prodcut 1']);

        // dd($product1, $product2);

        return new Response(['ap' => 2]);
    }

    public function show(Request $request, $product, $order)
    {

    }
}
