<?php

namespace Database\Seeders;

use App\Models\Brand;
use Framework\Kernel\Database\Seeders\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        Brand::factory(10,['title' => 10])->create();
    }
}