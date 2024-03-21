<?php

namespace Framework\Kernel\View\Contracts;

interface EngineInterface
{
    public function get(string $path, array $data = []): string;
}
