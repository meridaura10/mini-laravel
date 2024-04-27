<?php

namespace Framework\Kernel\Route\UrlGenerator;

use Closure;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Requests\Request;
use Framework\Kernel\Route\Contracts\UrlRoutableInterface;
use Framework\Kernel\Route\Exceptions\RouteNotFoundException;
use Framework\Kernel\Route\Route;
use Framework\Kernel\Route\RouteCollection;
use Framework\Kernel\Session\Contracts\SessionStoreInterface;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Str;

class UrlGenerator implements UrlGeneratorInterface
{
    protected RequestInterface $request;

    protected ?Closure $formatHostUsing = null;

    protected ?Closure $sessionResolver = null;

    protected ?Closure $formatPathUsing = null;

    protected ?RouteUrlGenerator $routeGenerator = null;

    protected ?string $cachedScheme = null;

    protected ?string $forceScheme = null;

    protected ?string $cachedRoot = null;

    protected ?string $forcedRoot = null;

    public function __construct(
        protected RouteCollection $routes,
        RequestInterface          $request,
        protected ?string         $assetRoot = null
    )
    {
        $this->setRequest($request);
    }

    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }

    public function route(string $name, mixed $parameters, bool $absolute = true): string
    {
        if (!is_null($route = $this->routes->getByName($name))) {
            return $this->toRoute($route, $parameters, $absolute);
        }

        throw new RouteNotFoundException("Route [{$name}] not defined.");
    }

    public function toRoute(Route $route, mixed $parameters, bool $absolute): string
    {
        $parameters = collect(Arr::wrap($parameters))->map(function ($value, $key) use ($route) {
            $value = $value instanceof UrlRoutableInterface && $route->bindingFieldFor($key)
                ? $value->{$route->bindingFieldFor($key)}
                : $value;

            return $value instanceof \BackedEnum ? $value->value : $value;
        })->all();

        return $this->routeUrl()->to(
            $route, $this->formatParameters($parameters), $absolute
        );
    }

    protected function routeUrl(): RouteUrlGenerator
    {
        if (!$this->routeGenerator) {
            $this->routeGenerator = new RouteUrlGenerator($this, $this->request);
        }

        return $this->routeGenerator;
    }

    public function format(string $root, string $path, ?Route $route = null): string
    {
        $path = '/' . trim($path, '/');

        if ($this->formatHostUsing) {
            $root = call_user_func($this->formatHostUsing, $root, $route);
        }

        if ($this->formatPathUsing) {
            $path = call_user_func($this->formatPathUsing, $path, $route);
        }

        return trim($root . $path, '/');
    }

    public function formatScheme(?bool $secure = null): string
    {
        if (!is_null($secure)) {
            return $secure ? 'https://' : 'http://';
        }

        if (is_null($this->cachedScheme)) {
            $this->cachedScheme = $this->forceScheme ?: $this->request->getScheme() . '://';
        }

        return $this->cachedScheme;
    }

    public function formatRoot(string $scheme,?string $root = null): string
    {
        if (is_null($root)) {
            if (is_null($this->cachedRoot)) {
                $this->cachedRoot = $this->forcedRoot ?: $this->request->root();
            }

            $root = $this->cachedRoot;
        }

        $start = str_starts_with($root, 'http://') ? 'http://' : 'https://';

        return preg_replace('~'.$start.'~', $scheme, $root, 1);
    }

    public function formatParameters(mixed $parameters): array
    {
        $parameters = Arr::wrap($parameters);

        foreach ($parameters as $key => $parameter) {
            if ($parameter instanceof UrlRoutableInterface) {
                $parameters[$key] = $parameter->getRouteKey();
            }
        }

        return $parameters;
    }

    public function asset(string $path, ?bool $secure = null): string
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $root = $this->assetRoot ?: $this->formatRoot($this->formatScheme($secure));

        return Str::finish($this->removeIndex($root), '/').trim($path, '/');
    }

    protected function removeIndex(string $root): string
    {
        $i = 'index.php';

        return str_contains($root, $i) ? str_replace('/'.$i, '', $root) : $root;
    }

    public function isValidUrl(string $path): bool
    {
        if (! preg_match('~^(#|//|https?://|(mailto|tel|sms):)~', $path)) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    public function previous(mixed $fallback = false): string
    {
        $referrer = $this->request->headers->get('referer');


        $url = $referrer ? $this->to($referrer) : $this->getPreviousUrlFromSession();

        if ($url) {
            return $url;
        } elseif ($fallback) {
            return $this->to($fallback);
        }

        return $this->to('/');
    }

    public function to(string $path,mixed $extra = [],?bool $secure = null): string
    {
        if($this->isValidUrl($path)){
            return $path;
        }

        $tail = implode('/', array_map(
                'rawurlencode', (array) $this->formatParameters($extra))
        );

        $root = $this->formatRoot($this->formatScheme($secure));

        [$path, $query] = $this->extractQueryString($path);

        return $this->format(
                $root, '/'.trim($path.'/'.$tail, '/')
            ).$query;
    }

    protected function extractQueryString(string $path): array
    {
        if (($queryPosition = strpos($path, '?')) !== false) {
            return [
                substr($path, 0, $queryPosition),
                substr($path, $queryPosition),
            ];
        }

        return [$path, ''];
    }

    protected function getPreviousUrlFromSession(): ?string
    {
        return $this->getSession()?->previousUrl();
    }

    protected function getSession(): ?SessionStoreInterface
    {
        if ($this->sessionResolver) {
            return call_user_func($this->sessionResolver);
        }

        return null;
    }

    public function setSessionResolver(callable $sessionResolver): static
    {
        $this->sessionResolver = $sessionResolver;

        return $this;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}