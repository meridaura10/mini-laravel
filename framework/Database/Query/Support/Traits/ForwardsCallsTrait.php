<?php

namespace Framework\Kernel\Database\Query\Support\Traits;

use BadMethodCallException;
use Error;

trait ForwardsCallsTrait
{
    protected function forwardCallTo(object $builder, string $method, array $parameters): mixed
    {
        try {

            return $builder->{$method}(...$parameters);

        } catch (BadMethodCallException|Error $exception) {
            dd($exception->getMessage());
        }
    }
}
