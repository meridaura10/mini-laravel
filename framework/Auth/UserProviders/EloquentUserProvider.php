<?php

namespace Framework\Kernel\Auth\UserProviders;

use Closure;
use Framework\Kernel\Auth\Contracts\AuthenticatableInterface;
use Framework\Kernel\Auth\Contracts\AuthUserProviderInterface;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Hashing\Contracts\HasherInterface;

class EloquentUserProvider implements AuthUserProviderInterface
{
    public function __construct(
        protected HasherInterface $hasher,
        protected string $model,
    )
    {

    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } elseif ($value instanceof Closure) {
                $value($query);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    protected function newModelQuery(?Model $model = null): BuilderInterface
    {
        return is_null($model)
            ? $this->createModel()->newQuery()
            : $model->newQuery();
    }

    public function createModel(): Model
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        if (is_null($plain = $credentials['password'])) {
            return false;
        }

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    public function updateRememberToken(AuthenticatableInterface $user, string $token): void
    {
        $user->setRememberToken($token);

        $timestamps = $user->timestamps;

        $user->timestamps = false;

        $user->save();

        $user->timestamps = $timestamps;
    }

    public function retrieveById(mixed $identifier): ?AuthenticatableInterface
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    public function retrieveByToken(mixed $identifier, string $token): ?AuthenticatableInterface
    {
        $model = $this->createModel();

        $retrievedModel = $this->newModelQuery($model)->where(
            $model->getAuthIdentifierName(), $identifier
        )->first();

        if (! $retrievedModel) {
            return null;
        }

        $rememberToken = $retrievedModel->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $retrievedModel : null;
    }
}