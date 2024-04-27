<?php

namespace Framework\Kernel\Facades\Services;

use Framework\Kernel\Facades\Facade;

/**
 * @method static \Framework\Kernel\Hashing\Services\BcryptHasher createBcryptDriver()
 * @method static array info(string $hashedValue)
 * @method static string make(string $value, array $options = [])
 * @method static bool check(string $value, string $hashedValue, array $options = [])
 * @method static string getDefaultDriver()
 * @method static mixed driver(string|null $driver = null)
 * @method static array getDrivers()
 *
 */

class Hash extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return 'hash';
    }
}