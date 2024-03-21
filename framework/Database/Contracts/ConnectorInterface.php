<?php

namespace Framework\Kernel\Database\Contracts;

use PDO;

interface ConnectorInterface
{
    public function connect(array $config): PDO;
}
