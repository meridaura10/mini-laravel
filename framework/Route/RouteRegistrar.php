<?php

namespace Framework\Kernel\Route;

use Framework\Kernel\Route\Contracts\RouterInterface;

class RouteRegistrar
{
    protected array $attributes = [];

    protected array $allowedAttributes = [
        'controller',
        'middleware',
        'name',
        'prefix',
    ];

    public function __construct(
        protected RouterInterface $router,
    ) {

    }

    public function attribute(string $key, mixed $value): static
    {
        if (! in_array($key, $this->allowedAttributes)) {
            throw new \Exception("Attribute [{$key}] does not exist.");
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    public function group(string|callable $routes): static
    {
        $this->router->group($this->attributes, $routes);

        return $this;
    }

    public function __call($method, $parameters): static
    {
        if (in_array($method, $this->allowedAttributes)) {
            if ($method === 'middleware') {
                return $this->attribute($method, $parameters);
            }

            return $this->attribute($method, array_key_exists(0, $parameters) ? $parameters[0] : true);
        }

        throw new \Exception("not attribute $method or route ");
    }
}
