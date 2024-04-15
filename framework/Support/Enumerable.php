<?php

namespace Framework\Kernel\Support;

use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Contracts\Support\Jsonable;

interface Enumerable extends Arrayable, \IteratorAggregate, Jsonable, \JsonSerializable
{

}