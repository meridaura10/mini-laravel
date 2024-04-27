<?php

namespace Framework\Kernel\Database\Exceptions;

class ClassMorphViolationException extends \RuntimeException
{
    public function __construct(protected $model)
    {
        $class = get_class($model);

        parent::__construct("No morph map defined for model [{$class}].");
    }
}