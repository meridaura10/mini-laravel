<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\Eloquent\Builder;
use Framework\Kernel\Database\Eloquent\Relations\Relation;
use Framework\Kernel\Http\Requests\Request;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\Response;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()->with(['brand' => function (Relation $builder) {
         $builder->select(['title','id']);
        }])->limit(10)->get();

        dd($products);

    }

    public function show(Request $request, $product)
    {

    }
}
