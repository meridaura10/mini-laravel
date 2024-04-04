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

    protected function forwardDecoratedCallTo(object $object,string $method,array $parameters): mixed
    {
        $result = $this->forwardCallTo($object, $method, $parameters);

        return $result === $object ? $this : $result;
    }
}
