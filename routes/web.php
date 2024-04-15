<?php

use App\Http\Controllers\Api\ProductController;
use Framework\Kernel\Facades\Services\Route;

Route::prefix('/products')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{product}/{level}', 'show');
    Route::post('/{product}', 'delete');
});
