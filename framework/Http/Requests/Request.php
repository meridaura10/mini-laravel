<?php

namespace Framework\Kernel\Http\Requests;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Http\HeaderBag;
use Framework\Kernel\Http\Requests\Bags\FileBag;
use Framework\Kernel\Http\Requests\Bags\InputBag;
use Framework\Kernel\Http\Requests\Bags\ParameterBag;
use Framework\Kernel\Http\Requests\Bags\ServerBag;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;

class Request implements RequestInterface
{
    protected ?ApplicationInterface $app = null;

    public ?InputBag $request = null;

    public ?InputBag $query = null;

    public ?ParameterBag $attributes = null;

    public ?InputBag $cookies = null;

    //    protected array $files = [];

    public ?ServerBag $server = null;

    public ?HeaderBag $headers = null;

    protected ?string $format = null;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        array $content = []

    ) {
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    public static function createFromGlobals(): static
    {

        return new static(...array_values(static::getGlobals()));
    }

    private static function getGlobals(): array
    {
        return [
            'GET' => $_GET,
            'POST' => $_POST,
            'ATTRIBUTES' => [],
            'COOKIE' => $_COOKIE,
            'FILES' => $_FILES,
            'SERVER' => $_SERVER,
        ];
    }

    public function initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null): void
    {
        $this->request = new InputBag($request);
        $this->query = new InputBag($query);
        $this->attributes = new ParameterBag($attributes);
        $this->cookies = new InputBag($cookies);
        //        $this->files = new FileBag($files);
        $this->server = new ServerBag($server);

        $this->headers = new HeaderBag($this->server->getHeaders());

        $this->format = null;
    }

    public function uri(): string
    {
        return ltrim($this->getRawUri(), '/');
    }

    private function getRawUri(): string
    {
        return strtok($this->server->get('REQUEST_URI'), '?');
    }

    public function method(): string
    {
        return $this->server->get('REQUEST_METHOD');
    }

    public function input(string $key, $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public static function createFrom(self $from, $to = null): RequestInterface
    {
        $request = $to ?: new static;

        $request->initialize();

        return $request;
    }

    public function setContainer(ApplicationInterface $app): static
    {
        $this->app = $app;

        return $this;
    }

    public function getRequestFormat(?string $default = 'html'): ?string
    {
        $this->format ??= $this->attributes->get('_format');

        return $this->format ?? $default;
    }

    public function getMimeType(string $format): ?string
    {
        return null;
        //        if (null === static::$formats) {
        //            static::initializeFormats();
        //        }
        //
        //        return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
    }
}
