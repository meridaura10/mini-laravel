<?php

namespace App\Models;

use Framework\Kernel\Database\Eloquent\Factories\Traits\HasFactoryTrait;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    use HasFactoryTrait;

    protected array $fillable = ['name', 'email', 'email_verified_at', 'password'];

    public function basket(): HasOne
    {
       return $this->hasOne(Basket::class);
    }
}
