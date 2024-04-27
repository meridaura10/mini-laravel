<?php

namespace Database\Seeders;

use App\Models\Basket;
use App\Models\BasketProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\User;
use Framework\Kernel\Database\Seeders\Seeder;
use Framework\Kernel\Facades\Services\Storage;

class DatabaseSeeder extends Seeder
{
    protected int $brandIndex = 0;

    public function run(): void
    {
        $this->call([
            UserSeeder::class
        ]);

        $files = Storage::disk('public')->files('/testing/brands');

        Brand::factory(8)
            ->has(Product::factory(150)
                ->has(Image::factory(3))
            )->create()->each(function (Brand $brand) use ($files) {
                if (!array_key_exists($this->brandIndex, $files)) {
                    $this->brandIndex = 0;
                }

                $file = $files[$this->brandIndex];

                $brand->image()->create([
                    'path' => $file,
                    'disk' => 'public',
                    'order' => 0,
                ]);
                $this->brandIndex++;
            });
    }
}