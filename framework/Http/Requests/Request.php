<?php

namespace Framework\Kernel\Http\Requests;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Http\HeaderBag;
use Framework\Kernel\Http\Requests\Bags\FileBag;
use Framework\Kernel\Http\Requests\Bags\InputBag;
use Framework\Kernel\Http\Requests\Bags\ParameterBag;
use Framework\Kernel\Http\Requests\Bags\ServerBag;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Requests\Exception\SuspiciousOperationException;
use Framework\Kernel\Http\Requests\Traits\CanBePrecognitiveTrait;
use Framework\Kernel\Http\Requests\Traits\InteractsWithContentTypesTrait;
use Framework\Kernel\Http\Requests\Traits\InteractsWithFlashDataTrait;
use Framework\Kernel\Http\Requests\Traits\InteractsWithInputTrait;
use Framework\Kernel\Route\Route;
use Framework\Kernel\Session\Contracts\SessionStoreInterface;
use Framework\Kernel\Support\Arr;
use RuntimeException;

class Request extends SRequest implements RequestInterface, Arrayable
{
    use InteractsWithContentTypesTrait,
        InteractsWithInputTrait,
        CanBePrecognitiveTrait,
        InteractsWithFlashDataTrait;

    protected ?InputBag $json = null;

    protected ?Closure $userResolver = null;
    protected ?Closure $routeResolver = null;

    protected ?SessionStoreInterface $session = null;


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
        return $this->getMethod();
    }


    public function setContainer(ApplicationInterface $app): static
    {
        $this->app = $app;

        return $this;
    }

    public function fullUrl(): string
    {
        $query = $this->getQueryString();

        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';

        return $query ? $this->url().$question.$query : $this->url();
    }

    public function url(): string
    {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
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


    protected function getInputSource(): InputBag
    {
        if($this->isJson()){
            return $this->json();
        }


        return in_array($this->getRealMethod(), ['GET', 'HEAD']) ? $this->query : $this->request;
    }

    public function getRealMethod(): string
    {
        return strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
    }

    public function json(?string $key = null,mixed $default = null): mixed
    {
        if (! isset($this->json)) {
            $this->json = new InputBag((array) json_decode($this->getContent(), true));
        }

        if (is_null($key)) {
            return $this->json;
        }

        return data_get($this->json->all(), $key, $default);
    }

    public static function createFrom(self $from, $to = null): RequestInterface
    {

        $request = $to ?: new static;

//        $files = array_filter($from->files->all());

        $request->initialize(
            $from->query->all(),
            $from->request->all(),
            $from->attributes->all(),
            $from->cookies->all(),
            $files ?? [],
            $from->server->all(),
            $from->getContent()
        );

//        $request->headers->replace($from->headers->all());

//        $request->setRequestLocale($from->getLocale());
//
//        $request->setDefaultRequestLocale($from->getDefaultLocale());

        $request->setJson($from->json());

        if ($from->hasSession() && $session = $from->session()) {
            $request->setSession($session);
        }

        $request->setUserResolver($from->getUserResolver());

        $request->setRouteResolver($from->getRouteResolver());

        return $request;
    }

    public function setUserResolver(Closure $callback): static
    {
        $this->userResolver = $callback;

        return $this;
    }

    public function getUserResolver(): Closure
    {
        return $this->userResolver ?: function () {
            //
        };
    }

    public function getRouteResolver(): Closure
    {
        return $this->routeResolver ?: function () {
            //
        };
    }

    public function route(?string $param = null, mixed $default = null): Route|null|string
    {
        $route = call_user_func($this->getRouteResolver());

        if(is_null($route) || is_null($param)){
            return $route;
        }

        return $route->parameter($param, $default);
    }

    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function root(): string
    {
        return rtrim($this->getSchemeAndHttpHost().$this->getBaseUrl(), '/');
    }

    public function ajax(): bool
    {
        return $this->isXmlHttpRequest();
    }

    public function pjax(): bool
    {
        return $this->headers->get('X-PJAX') == true;
    }

    public function setJson(?InputBag $json): static
    {
        $this->json = $json;

        return $this;
    }

    public function toArray(): array
    {
        return $this->all();
    }

    public function setRouteResolver(Closure $callback): static
    {
        $this->routeResolver = $callback;

        return $this;
    }

    public function setSession(SessionStoreInterface $session): void
    {
        $this->session = $session;
    }

    public function hasSession(bool $skipIfUninitialized = false): bool
    {
        return ! is_null($this->session);
    }

    public function session(): SessionStoreInterface
    {
        if (! $this->hasSession()) {
            throw new RuntimeException('Session store not set on request.');
        }

        return $this->session;
    }

    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }
}
