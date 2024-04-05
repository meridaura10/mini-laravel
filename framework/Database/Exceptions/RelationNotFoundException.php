<?php

namespace Framework\Kernel\Database\Exceptions;

class RelationNotFoundException extends \RuntimeException
{
    public $model;

    public $relation;

    public static function make($model, $relation, $type = null)
    {
        $class = get_class($model);

        $instance = new static(
            is_null($type)
                ? "Call to undefined relationship [{$relation}] on model [{$class}]."
                : "Call to undefined relationship [{$relation}] on model [{$class}] of type [{$type}].",
        );

        $instance->model = $class;
        $instance->relation = $relation;

        return $instance;
    }
}