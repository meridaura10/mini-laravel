<?php

use Framework\Kernel\Facades\Services\Route;

\Framework\Kernel\Facades\Services\Auth::routes([
    'namespace' => 'App\Http\Controllers\Web',
]);

Route::get('/', \App\Http\Controllers\Web\HomeController::class)->name('home');

Route::prefix('/basket')->controller(\App\Http\Controllers\Web\BasketController::class)->name('basket')->group(function (){
    Route::get('/', 'show')->name('show');
});

Route::prefix('/products')->controller(\App\Http\Controllers\Web\ProductController::class)->name('product.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{product}/{level}', 'show')->name('show');
    Route::post('/{product}', 'delete')->name('delete');
});

Route::prefix('/brands')->controller(\App\Http\Controllers\Web\BrandController::class)->name('brand.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{brand}', 'show')->name('show');
    Route::post('/{brand}', 'delete')->name('delete');
});