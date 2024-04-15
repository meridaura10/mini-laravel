<?php

namespace Framework\Kernel\Route\Exceptions;

class BackedEnumCaseNotFoundException extends \RuntimeException
{
    public function __construct($backedEnumClass, $case)
    {
        parent::__construct("Case [{$case}] not found on Backed Enum [{$backedEnumClass}].");
    }
}