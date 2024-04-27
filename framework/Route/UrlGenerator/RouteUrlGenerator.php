<?php

namespace Framework\Kernel\Route\UrlGenerator;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Route\Route;
use Framework\Kernel\Support\Arr;

class RouteUrlGenerator
{
    public function __construct(
        protected UrlGeneratorInterface $url,
        protected RequestInterface $request,
    ) {

    }

    public function to(Route $route, array $parameters, bool $absolute = false): string
    {
        $domain = null;

        $uri = $this->addQueryString($this->url->format(
            $root = $this->replaceRootParameters($route, $domain, $parameters),
            $this->replaceRouteParameters($route->uri(), $parameters),
            $route
        ), $parameters);

        return $uri;
    }

    protected function replaceRouteParameters(string $path, array &$parameters): string
    {
        $path = $this->replaceNamedParameters($path, $parameters);

        $path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            $parameters = array_merge($parameters);

            return (! isset($parameters[0]) && ! str_ends_with($match[0], '?}'))
                ? $match[0]
                : Arr::pull($parameters, 0);
        }, $path);

        return trim(preg_replace('/\{.*?\?\}/', '', $path), '/');
    }

    protected function replaceNamedParameters(string $path,array &$parameters): string
    {
        return preg_replace_callback('/\{(.*?)(\?)?\}/', function ($m) use (&$parameters) {
            if (isset($parameters[$m[1]]) && $parameters[$m[1]] !== '') {
                return Arr::pull($parameters, $m[1]);
            } elseif (isset($this->defaultParameters[$m[1]])) {
                return $this->defaultParameters[$m[1]];
            } elseif (isset($parameters[$m[1]])) {
                Arr::pull($parameters, $m[1]);
            }

            return $m[0];
        }, $path);
    }

    protected function replaceRootParameters(Route $route,?string $domain,array &$parameters): string
    {
        $scheme = $this->getRouteScheme($route);

        return $this->replaceRouteParameters(
            $this->url->formatRoot($scheme, $domain), $parameters
        );
    }

    protected function getRouteScheme(Route $route): string
    {
        if ($route->httpOnly()) {
            return 'http://';
        } elseif ($route->httpsOnly()) {
            return 'https://';
        }

        return $this->url->formatScheme();
    }

    protected function addQueryString(string $uri, array $parameters): mixed
    {
        if (! is_null($fragment = parse_url($uri, PHP_URL_FRAGMENT))) {
            $uri = preg_replace('/#.*/', '', $uri);
        }

        $uri .= $this->getRouteQueryString($parameters);

        return is_null($fragment) ? $uri : $uri."#{$fragment}";
    }

    protected function getRouteQueryString(array $parameters): string
    {
        if (count($parameters) === 0) {
            return '';
        }

        $query = Arr::query(
            $keyed = $this->getStringParameters($parameters)
        );

        if (count($keyed) < count($parameters)) {
            $query .= '&'.implode(
                    '&', $this->getNumericParameters($parameters)
                );
        }

        $query = trim($query, '&');

        return $query === '' ? '' : "?{$query}";
    }

    protected function getStringParameters(array $parameters): array
    {
        return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    protected function getNumericParameters(array $parameters): array
    {
        return array_filter($parameters, 'is_numeric', ARRAY_FILTER_USE_KEY);
    }
}