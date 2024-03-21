<?php

namespace Framework\Kernel\Http\Exception;

class MethodNotAllowedHttpException extends \Exception
{
    public function __construct($message, $code = 405, mixed $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
