<?php

use Framework\Kernel\Http\Contracts\KernelInterface;
use Framework\Kernel\Http\Requests\Request;

define('APP_PATH', dirname(__DIR__));

require_once APP_PATH.'/vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

\Framework\Kernel\Support\Env::init(app()->basePath());

$kernel = $app->make(KernelInterface::class);

$kernel->handle(
    Request::createFromGlobals(),
)->send();
