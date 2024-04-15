<?php

namespace Framework\Kernel\Database\Exceptions;

class JsonEncodingException extends \RuntimeException
{
    public static function forModel(mixed $model,string $message): static
    {
        return new static('Error encoding model ['.get_class($model).'] with ID ['.$model->getKey().'] to JSON: '.$message);
    }
}