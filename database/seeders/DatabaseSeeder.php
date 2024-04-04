<?php

namespace Database\Seeders;

use App\Models\Basket;
use App\Models\BasketProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Framework\Kernel\Database\Seeders\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->has(Basket::factory(3)
                ->has(BasketProduct::factory(3)
                    ->for(Product::factory(2)
                        ->for(Brand::factory()))))
            ->create();
    }
}