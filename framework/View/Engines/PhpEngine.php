<?php

namespace Framework\Kernel\View\Engines;

use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\View\Contracts\EngineInterface;
use Throwable;

class PhpEngine implements EngineInterface
{
    public function __construct(
        protected FilesystemInterface $files,
    ) {
        //
    }

    public function get(string $path, array $data = []): string
    {
        return $this->evaluatePath($path, $data);
    }

    protected function evaluatePath(string $path, array $data): string
    {
        $obLevel = ob_get_level();

        ob_start();

        try {
            $this->files->getRequire($path, $data);
        } catch (Throwable $exception) {
            $this->handleViewException($exception, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    protected function handleViewException(Throwable $e, $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}
