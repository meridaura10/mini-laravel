<?php

namespace Framework\Kernel\Contracts\Support;

interface Jsonable
{
    public function toJson(int $options = 0): string;
}
