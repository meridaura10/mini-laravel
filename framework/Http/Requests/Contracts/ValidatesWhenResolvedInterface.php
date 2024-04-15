<?php

namespace Framework\Kernel\Http\Requests\Contracts;

interface ValidatesWhenResolvedInterface
{
    public function validateResolved(): void;
}