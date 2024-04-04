<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Framework\Kernel\Http\Requests\Request;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\Response;

class ProductController extends Controller
{
    public function index()
    {
        Product::query()->with([
        'post.comment.user' => function ($query) {
            // Додаткові опції для відношення користувача
            $query->where('is_active', true);
        },
        'post.comment.user.basket', // Завантаження відношення корзини для користувача
    ]);

    }

    public function show(Request $request, $product)
    {

    }
}
