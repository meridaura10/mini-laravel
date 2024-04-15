<?php

namespace Framework\Kernel\Http\Requests;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Http\HeaderBag;
use Framework\Kernel\Http\Requests\Bags\InputBag;
use Framework\Kernel\Http\Requests\Bags\ParameterBag;
use Framework\Kernel\Http\Requests\Bags\ServerBag;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Requests\Support\AcceptHeader;

class SRequest
{
    protected ?ApplicationInterface $app = null;

    public ?InputBag $request = null;

    public ?InputBag $query = null;

    public ?ParameterBag $attributes = null;

    public ?InputBag $cookies = null;

    protected array $files = [];

    public ?ServerBag $server = null;

    public ?HeaderBag $headers = null;

    protected ?string $format = null;

    protected string|false|null $content = null;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        null|string $content = null,

    ) {
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
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


        $this->content = $content;
//        $this->languages = null;
//        $this->charsets = null;
//        $this->encodings = null;
//        $this->acceptableContentTypes = null;
//        $this->pathInfo = null;
//        $this->requestUri = null;
//        $this->baseUrl = null;
//        $this->basePath = null;
//        $this->method = null;
        $this->format = null;
    }

    public function getContent(bool $asResource = false): string
    {
        $currentContentIsResource = \is_resource($this->content);

        if (true === $asResource) {
            if ($currentContentIsResource) {
                rewind($this->content);

                return $this->content;
            }

            // Content passed in parameter (test)
            if (\is_string($this->content)) {
                $resource = fopen('php://temp', 'r+');
                fwrite($resource, $this->content);
                rewind($resource);

                return $resource;
            }

            $this->content = false;

            return fopen('php://input', 'r');
        }

        if ($currentContentIsResource) {
            rewind($this->content);

            return stream_get_contents($this->content);
        }

        if (null === $this->content || false === $this->content) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }


    public static function createFromGlobals(): static
    {
        $request = self::createRequestFromFactory($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);

        if (str_starts_with($request->headers->get('CONTENT_TYPE', ''), 'application/x-www-form-urlencoded')
            && \in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new InputBag($data);
        }

        return $request;
    }

    private static function createRequestFromFactory(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null): static
    {
        return new static($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    public function isXmlHttpRequest(): bool
    {
        return 'XMLHttpRequest' == $this->headers->get('X-Requested-With');
    }

    public function getAcceptableContentTypes(): array
    {
        return $this->acceptableContentTypes ??= array_map('strval', array_keys(AcceptHeader::fromString($this->headers->get('Accept'))->all()));
    }
}