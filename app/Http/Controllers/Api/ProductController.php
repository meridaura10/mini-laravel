<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductShowEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Models\Product;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Eloquent\Relations\Relation;
use Framework\Kernel\Http\Requests\Request;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\Response;

class ProductController extends Controller
{
    public function index(): ResponseInterface
    {
        $products = Product::query()
            ->with('brand')
            ->where('id',1)
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function show(Product $product, ProductShowEnum $level, Request $request)
    {
        dd($product, $level,$request);
    }

    public function store(ProductStoreRequest $request)
    {
//        $data = $request->validated();


        return new Response($request);
    }
}
