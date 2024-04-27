<?php

use Framework\Kernel\Facades\Services\Route;

Route::get('/',  \App\Http\Controllers\Web\Admin\HomeController::class)->name('home');

Route::prefix('brands')->name('brand.')->controller(\App\Http\Controllers\Web\Admin\BrandController::class)->group(function (){
    Route::get('/', 'index')->name('index');
    Route::post('/update/{brand}','update')->name('update');
    Route::get('/edit/{brand}','edit')->name('edit');
    Route::post('/destroy{brand}','destroy')->name('destroy');
});
