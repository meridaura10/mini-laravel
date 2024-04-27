<?php

namespace Framework\Kernel\Auth\Guards;

use Framework\Kernel\Auth\Contracts\AuthenticatableInterface;
use Framework\Kernel\Auth\Contracts\AuthStatefulGuardInterface;
use Framework\Kernel\Auth\Contracts\AuthUserProviderInterface;
use Framework\Kernel\Auth\Support\Recaller;
use Framework\Kernel\Auth\Traits\GuardHelpersTrait;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Responses\Headers\Cookie;
use Framework\Kernel\Session\Contracts\SessionStoreInterface;
use Framework\Kernel\Support\Str;
use Framework\Kernel\Http\Cookie\Contracts\QueueingFactoryInterface;
use Framework\Kernel\Support\Timebox;
use RuntimeException;

class SessionGuard implements AuthStatefulGuardInterface
{
    use GuardHelpersTrait;

    protected bool $loggedOut = false;

    protected ?AuthenticatableInterface $user = null;

    protected ?QueueingFactoryInterface $cookie = null;

    protected ?AuthenticatableInterface $lastAttempted = null;

    protected bool $recallAttempted = false;

    protected bool $viaRemember = false;

    protected int $rememberDuration = 576000;

    public function __construct(
        protected string                    $name,
        protected AuthUserProviderInterface $provider,
        protected SessionStoreInterface     $session,
        protected ?RequestInterface         $request = null,
        protected ?Timebox                  $timebox = null,

    )
    {
        $this->timebox = $this->timebox ?: new Timebox();
    }

    public function user(): ?AuthenticatableInterface
    {
        if ($this->loggedOut) {
            return null;
        }

        if (!is_null($this->user)) {
            return $this->user;
        }

        $id = $this->session->get($this->getName());

        if ($id) {
            $this->user = $this->provider->retrieveById($id);
        }

        if (is_null($this->user) && !is_null($recaller = $this->recaller())) {
            $this->user = $this->userFromRecaller($recaller);

            if ($this->user) {
                $this->updateSession($this->user->getAuthIdentifier());
            }
        }

        return $this->user;
    }

    protected function userFromRecaller(Recaller $recaller): mixed
    {
        if (! $recaller->valid() || $this->recallAttempted) {
            return null;
        }

        $this->recallAttempted = true;

        $this->viaRemember = ! is_null($user = $this->provider->retrieveByToken(
            $recaller->id(), $recaller->token()
        ));

        return $user;
    }

    protected function recaller(): ?Recaller
    {
        if (is_null($this->request)) {
            return null;
        }

        if ($recaller = $this->request->cookies->get($this->getRecallerName())) {
            return new Recaller($recaller);
        }

        return null;
    }

    protected function clearUserDataFromStorage(): void
    {
        $this->session->remove($this->getName());

        $this->getCookieJar()->unqueue($this->getRecallerName());

        if (! is_null($this->recaller())) {
            $this->getCookieJar()->queue(
                $this->getCookieJar()->forget($this->getRecallerName())
            );
        }
    }

    public function getRecallerName(): string
    {
        return 'remember_' . $this->name . '_' . sha1(static::class);
    }

    public function getName(): string
    {
        return 'login_' . $this->name . '_' . sha1(static::class);
    }

    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);

            return true;
        }

        return false;
    }

    public function login(AuthenticatableInterface $user, bool $remember = false): void
    {
        $this->updateSession($user->getAuthIdentifier());

        if ($remember) {
            $this->ensureRememberTokenIsSet($user);

            $this->queueRecallerCookie($user);
        }



        $this->setUser($user);
    }

    protected function queueRecallerCookie(AuthenticatableInterface $user): void
    {
        $this->getCookieJar()->queue($this->createRecaller(
            $user->getAuthIdentifier() . '|' . $user->getRememberToken() . '|' . $user->getAuthPassword()
        ));
    }

    protected function createRecaller(string $value): Cookie
    {
        return $this->getCookieJar()->make($this->getRecallerName(), $value, $this->getRememberDuration());
    }

    protected function getRememberDuration(): int
    {
        return $this->rememberDuration;
    }

    public function setUser(AuthenticatableInterface $user): static
    {
        $this->user = $user;

        $this->loggedOut = false;

        return $this;
    }

    protected function ensureRememberTokenIsSet(AuthenticatableInterface $user): void
    {
        if (empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }
    }

    protected function cycleRememberToken(AuthenticatableInterface $user): void
    {
        $token = Str::random(60);

        $this->provider->updateRememberToken($user, $token);
    }


    protected function updateSession(string $id): void
    {
        $this->session->put($this->getName(), $id);

        $this->session->migrate(true);
    }

    protected function hasValidCredentials(mixed $user, array $credentials): bool
    {
        return $this->timebox->call(function (Timebox $timebox) use ($user, $credentials) {
            $validated = !is_null($user) && $this->provider->validateCredentials($user, $credentials);

            if ($validated) {
                $timebox->returnEarly();
            }

            return $validated;
        }, 200 * 1000);
    }

    public function logout(): void
    {
        $user = $this->user();

        $this->clearUserDataFromStorage();

        if (!is_null($this->user) && !empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }

        $this->user = null;

        $this->loggedOut = true;
    }

    public function setCookieJar(QueueingFactoryInterface $cookie): void
    {
        $this->cookie = $cookie;
    }

    public function getCookieJar(): QueueingFactoryInterface
    {
        if (!isset($this->cookie)) {
            throw new RuntimeException('Cookie jar has not been set.');
        }

        return $this->cookie;
    }

    public function setRequest(RequestInterface $request): static
    {
        $this->request = $request;

        return $this;
    }
}