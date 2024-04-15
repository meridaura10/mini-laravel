<?php

namespace Framework\Kernel\Foundation\Exceptions;

use Throwable;

class ErrorPageViewModel
{
    public function __construct(
        protected Throwable $throwable,
        protected Report $report,
    )
    {

    }

    public function getAssetCssContents(string $asset): string
    {
        return $this->getAssetContents('css', $asset);
    }

    protected function getAssetContents(string $type, string $asset): string
    {
        $assetPath = __DIR__."/resources/$type/{$asset}";

        return (string) file_get_contents($assetPath);
    }

    public function title(): string
    {
        return $this->throwable->getMessage();
    }

    public function trace(): array
    {
        return $this->throwable->getTrace();
    }
}