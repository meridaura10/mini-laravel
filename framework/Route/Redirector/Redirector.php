<?php

namespace Framework\Kernel\Route\Redirector;

use Framework\Kernel\Http\Responses\RedirectResponse;
use Framework\Kernel\Route\Redirector\Contracts\RedirectorInterface;
use Framework\Kernel\Route\UrlGenerator\UrlGeneratorInterface;
use Framework\Kernel\Session\Contracts\SessionStoreInterface;

class Redirector implements RedirectorInterface
{
    protected ?SessionStoreInterface $session = null;

    public function __construct(
        protected UrlGeneratorInterface $generator
    )
    {

    }

    public function setSession(SessionStoreInterface $session): void
    {
        $this->session = $session;
    }

    public function to(string $path, int $status = 302, array $headers = [], ?bool $secure = null): RedirectResponse
    {
        return $this->createRedirect($this->generator->to($path, [], $secure), $status, $headers);
    }

    public function back(int $status = 302,array $headers = [],bool $fallback = false): RedirectResponse
    {
        return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
    }

    protected function createRedirect(string $path, int $status, array $headers): RedirectResponse
    {
        return tap(new RedirectResponse($path, $status, $headers), function (RedirectResponse $redirect) {
            if (isset($this->session)) {
                $redirect->setSession($this->session);
            }

            $redirect->setRequest($this->generator->getRequest());
        });
    }

    public function route(string $route,array $parameters = [],int $status = 302,array $headers = []): RedirectResponse
    {
        return $this->to($this->generator->route($route, $parameters), $status, $headers);
    }

    public function intended(mixed $default = '/', int $status = 302, array $headers = [], ?bool $secure = null): RedirectResponse
    {
        $path = $this->session->pull('url.intended', $default);

        return $this->to($path, $status, $headers, $secure);
    }

    public function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->generator;
    }
}