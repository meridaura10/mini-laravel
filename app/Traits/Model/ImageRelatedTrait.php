<?php

namespace App\Traits\Model;

use App\Models\Image;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;

trait ImageRelatedTrait
{
    public function image(): QueryBuilderInterface
    {
        return $this->morphOne(Image::class, 'relation')->orderBy('order', 'asc');
    }

    public function images(): QueryBuilderInterface
    {
        return $this->morphMany(Image::class, 'relation')->orderBy('order');
    }
}