<?php

namespace Framework\Kernel\Route;

class RouteCompiled
{
    protected array $variables = [];

    protected string $staticPrefix = '';

    protected string $regex = '';

    public function __construct(string $uri)
    {
        $this->variables = $this->generateVariables($uri);
        $this->staticPrefix = preg_replace('/\{(\w+)\}/', '', $uri);
        $this->regex = $this->generateRegex($uri);
    }

    protected function generateRegex(string $uri): string
    {
        $pattern = preg_replace_callback('/\{(.*?)\}/', function ($matches) {
            return '(?P<'.$matches[1].'>[^/]+)';
        }, $uri);

        return '@^'.$pattern.'$@';
    }

    protected function generateVariables(string $uri): array
    {
        $variables = [];
        if (preg_match_all('/\{(\w+)\}/', $uri, $matches)) {
            $variables = $matches[1];
        }

        return $variables;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function staticPrefix(): string
    {
        return $this->staticPrefix;
    }
}
