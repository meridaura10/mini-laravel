<?php

use App\Http\Controllers\Api\ProductController;
use Framework\Kernel\Facades\Services\Route;

Route::prefix('products/')->name('product.')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/store','store')->name('store');
    Route::get('/{product}', 'show')->name('show');
});
