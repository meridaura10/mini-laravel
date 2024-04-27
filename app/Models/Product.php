<?php

namespace App\Models;

use App\Traits\Model\ImageRelatedTrait;
use Framework\Kernel\Database\Eloquent\Factories\Traits\HasFactoryTrait;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactoryTrait, ImageRelatedTrait;

    protected array $fillable = ['price', 'brand_id', 'title'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
