<?php

namespace App\Models;

use Framework\Kernel\Database\Eloquent\Model;

class Product extends Model
{
    protected array $fillable = ['title'];
}
