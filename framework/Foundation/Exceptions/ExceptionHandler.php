<?php

namespace Framework\Kernel\Foundation\Exceptions;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Contracts\Support\Responsable;
use Framework\Kernel\Foundation\Exceptions\Contracts\ExceptionHandlerInterface;
use Framework\Kernel\Foundation\Exceptions\Contracts\ExceptionRendererInterface;
use Framework\Kernel\Http\Exception\HttpExceptionInterface;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\JsonResponse;
use Framework\Kernel\Http\Responses\Response;
use Framework\Kernel\Route\Router;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Validator\Exceptions\ValidationException;
use Throwable;
use WeakMap;

class ExceptionHandler implements ExceptionHandlerInterface
{
    protected WeakMap $reportedExceptionMap;

    protected array $renderCallbacks = [];

    protected array $exceptionMap = [];

    public function __construct(protected ApplicationInterface $app)
    {
        $this->reportedExceptionMap = new WeakMap();

        $this->register();
    }

    public function register(): void
    {

    }

    public function report(Throwable $e): void
    {

    }

    public function render(RequestInterface $request, Throwable $e): ResponseInterface
    {
        $e = $this->mapException($e);

        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return Router::toResponse($request, $response);
        }

        if ($e instanceof Responsable) {
            return $e->toResponse($request);
        }

        $e = $this->prepareException($e);

        return match (true) {
//            $e instanceof HttpResponseException => $e->getResponse(),
//            $e instanceof AuthenticationException => $this->unauthenticated($request, $e),
            $e instanceof ValidationException => $this->convertValidationExceptionToResponse($e, $request),
            default => $this->renderExceptionResponse($request, $e),
        };
    }

    protected function renderExceptionResponse(RequestInterface $request, Throwable $e): ResponseInterface
    {
        return $this->shouldReturnJson($request, $e)
            ? $this->prepareJsonResponse($request, $e)
            : $this->prepareResponse($request, $e);
    }

    protected function prepareResponse(RequestInterface $request, Throwable $e): ResponseInterface
    {

        if (!$this->isHttpException($e) && config('app.debug')) {
            return $this->toIlluminateResponse($this->convertExceptionToResponse($e), $e)->prepare($request);
        }
    }


    protected function toIlluminateResponse(ResponseInterface $response): ResponseInterface
    {
        return new Response(
            $response->getContent(), $response->getStatusCode(), $response->headers->all()
        );
    }

    protected function convertExceptionToResponse(Throwable $e): ResponseInterface
    {
        return new Response(
            $this->renderExceptionContent($e),
            $this->isHttpException($e) ? $e->getStatusCode() : 500,
            $this->isHttpException($e) ? $e->getHeaders() : []
        );
    }

    protected function renderExceptionContent(Throwable $e): string
    {
        return app(ExceptionRendererInterface::class)->render($e);
    }

    protected function prepareJsonResponse(RequestInterface $request, Throwable $e): ResponseInterface
    {
        return new JsonResponse(
            $this->convertExceptionToArray($e),
            $this->isHttpException($e) ? $e->getStatusCode() : 500,
            $this->isHttpException($e) ? $e->getHeaders() : [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    protected function isHttpException(Throwable $e): bool
    {
        return $e instanceof HttpExceptionInterface;
    }

    protected function convertExceptionToArray(Throwable $e): array
    {
        return config('app.debug') ? [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->map(fn($trace) => Arr::except($trace, ['args']))->all(),
        ] : [
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
        ];
    }



    protected function convertValidationExceptionToResponse(ValidationException $e, RequestInterface $request): ResponseInterface
    {
        if ($e->response) {
            return $e->response;
        }

        return $this->shouldReturnJson($request, $e)
            ? $this->invalidJson($request, $e)
            : $this->invalid($request, $e);
    }

    protected function invalidJson(RequestInterface $request, ValidationException $exception): ResponseInterface
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    protected function shouldReturnJson(RequestInterface $request, Throwable $e): bool
    {
        return $request->expectsJson();
    }


    protected function prepareException(Throwable $e): Throwable
    {
        return $e;
    }


    protected function mapException(Throwable $e): Throwable
    {
        if (method_exists($e, 'getInnerException') &&
            ($inner = $e->getInnerException()) instanceof Throwable) {
            return $inner;
        }

        foreach ($this->exceptionMap as $class => $mapper) {
            if (is_a($e, $class)) {
                return $mapper($e);
            }
        }

        return $e;
    }

}