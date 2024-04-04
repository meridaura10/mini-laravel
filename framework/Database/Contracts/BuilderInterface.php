<?php

namespace Framework\Kernel\Database\Contracts;

use Framework\Kernel\Database\Connection;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Database\Query\QueryBuilder;


/**
 * This interface is intentionally empty and exists to improve IDE support.
 *
 * @mixin \Framework\Kernel\Database\Eloquent\Builder
 */

interface BuilderInterface extends QueryBuilderInterface
{

}
