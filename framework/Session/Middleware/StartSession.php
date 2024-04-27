<?php

namespace Framework\Kernel\Session\Middleware;

use Carbon\Carbon;
use Closure;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\Headers\Cookie;
use Framework\Kernel\Route\Route;
use Framework\Kernel\Session\Contracts\SessionStoreInterface;
use Framework\Kernel\Session\SessionManager;

class StartSession
{
    public function __construct(
        protected SessionManager $manager,
    )
    {

    }

    public function handle(RequestInterface $request, \Closure $next): mixed
    {
        if (! $this->sessionConfigured()) {
            return $next($request);
        }

        $session = $this->getSession($request);

        return $this->handleStatefulRequest($request, $session, $next);
    }

    protected function handleStatefulRequest(RequestInterface $request, SessionStoreInterface $session, Closure $next): mixed
    {
        $request->setSession(
            $this->startSession($request, $session),
        );

//        $this->collectGarbage($session);


        $response = $next($request);

        $this->storeCurrentUrl($request, $session);

        $this->addCookieToResponse($response, $session);

        $this->saveSession($request);

        return $response;
    }

    protected function storeCurrentUrl(RequestInterface $request,SessionStoreInterface $session)
    {
        if ($request->isMethod('GET') &&
            $request->route() instanceof Route &&
            ! $request->ajax() &&
            ! $request->prefetch() &&
            ! $request->isPrecognitive()) {
            $session->setPreviousUrl($request->fullUrl());
        }
    }

    protected function addCookieToResponse(ResponseInterface $response, SessionStoreInterface $session): void
    {
        if ($this->sessionIsPersistent($config = $this->manager->getSessionConfig())) {
            $response->headers->setCookie(new Cookie(
                $session->getName(),
                $session->getId(),
                $this->getCookieExpirationDate(),
                $config['path'],
                $config['domain'],
                $config['secure'] ?? false,
                $config['http_only'] ?? true,
                false,
                $config['same_site'] ?? null,
                $config['partitioned'] ?? false
            ));
        }
    }

    protected function getCookieExpirationDate(): \DateTimeInterface|int
    {
        $config = $this->manager->getSessionConfig();

        return $config['expire_on_close'] ? 0 : Carbon::instance(
            Carbon::now()->addRealMinutes($config['lifetime'])
        );
    }

    protected function sessionIsPersistent(?array $config = null): bool
    {
        $config = $config ?: $this->manager->getSessionConfig();

        return ! is_null($config['driver'] ?? null);
    }

    protected function saveSession(RequestInterface $request): void
    {
        if (! $request->isPrecognitive()) {
            $this->manager->driver()->save();
        }
    }

    protected function startSession(RequestInterface $request, SessionStoreInterface $session): SessionStoreInterface
    {
        return tap($session, function (SessionStoreInterface $session) use ($request){
            $session->setRequestOnHandler($request);

            $session->start();
        });
    }

    public function getSession(RequestInterface $request): SessionStoreInterface
    {
        return tap($this->manager->driver(), function (SessionStoreInterface $session) use ($request) {
            $session->setId($request->cookies->get($session->getName()));
        });
    }

    protected function sessionConfigured(): bool
    {
        return ! is_null($this->manager->getSessionConfig()['driver'] ?? null);
    }

}