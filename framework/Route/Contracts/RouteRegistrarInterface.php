<?php

namespace Framework\Kernel\Route\Contracts;

use Framework\Kernel\Route\Route;

interface RouteRegistrarInterface
{
    public function get(string $uri, string|array $action): Route;

    public function post(string $uri, string|array $action): Route;

    public function prefix(string $uri): RouteGroupInterface;
}
