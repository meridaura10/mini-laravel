<?php

namespace Framework\Kernel\Foundation\Auth\Model;

use Framework\Kernel\Auth\Contracts\AuthenticatableInterface;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Foundation\Auth\Traits\AuthenticatableTrait;

class User extends Model implements AuthenticatableInterface
{
    use AuthenticatableTrait;
}