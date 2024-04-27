<?php

namespace App\Models;

use Framework\Kernel\Database\Eloquent\Factories\Traits\HasFactoryTrait;
use Framework\Kernel\Database\Eloquent\Relations\HasOne;

class User extends \Framework\Kernel\Foundation\Auth\Model\User
{
    use HasFactoryTrait;

    protected array $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token'
    ];

    protected array $hidden = [
        'password',
        'remember_token',
    ];

    public function basket(): HasOne
    {
        return $this->hasOne(Basket::class);
    }
}
