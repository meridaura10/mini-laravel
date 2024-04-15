<?php

namespace Framework\Kernel\Foundation\Exceptions;

use Framework\Kernel\Facades\Services\Flare;
use Framework\Kernel\Foundation\Exceptions\Contracts\ExceptionPageInterface;
use Framework\Kernel\Foundation\Renderer;
use Framework\Kernel\Support\Arr;
use Throwable;

class ExceptionPage implements ExceptionPageInterface
{
    protected array $data = [];

    protected bool $isDebug;

    public function __construct()
    {
        $this->isDebug = config('app.debug');
    }
    protected function parse(Throwable $throwable): void
    {
        $this->data = $this->isDebug ? $this->getDataDebug($throwable) : $this->getDataNotDebug($throwable);
    }

    protected function getDataNotDebug(Throwable $throwable): array
    {
        return [
            'message' => 'Error server',
        ];
    }

    public function getDataDebug(Throwable $e): array
    {
        return [
            'message' => $e->getMessage(),
            'code' => file($e->getFile()),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->map(fn($trace) => Arr::except($trace, ['args']))->all(),
        ];
    }

    public function render(Throwable $throwable): string
    {
        $this->parse($throwable);

        $report ??= $this->createReport($throwable);

        $viewModel = $this->createErrorPageViewModel([
            $throwable,
            $report,
        ]);

        return view(self::view('errorPage'),[
            'viewModel' => $viewModel,
        ])->render();
    }

    public static function view(string $viewName): string
    {
        return "error::$viewName";
    }

    protected function createReport(): Report
    {
        return new Report();
    }

    protected function createErrorPageViewModel(array $data): ErrorPageViewModel
    {
        return new ErrorPageViewModel(...$data);
    }
}