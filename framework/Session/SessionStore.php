<?php

namespace Framework\Kernel\Session;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Session\Contracts\SessionStoreInterface;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Str;
use Framework\Kernel\Support\ViewErrorBag;
use Framework\Kernel\Validator\Bags\MessageBag;
use SessionHandlerInterface;
use stdClass;

class SessionStore implements SessionStoreInterface
{
    protected string $id;

    protected array $attributes = [];

    protected bool $started = false;

    public function __construct(
        protected string                  $name,
        protected SessionHandlerInterface $handler,
        ?string                           $id = null,
        protected string                  $serialization = 'php',
    )
    {
        $this->setId($id);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $key, $default);
    }

    public function setId(?string $id): void
    {
        $this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
    }

    public function getOldInput(?string $key = null,mixed $default = null): mixed
    {
        return Arr::get($this->get('_old_input', []), $key, $default);
    }

    public function save(): void
    {
        $this->ageFlashData();

        $this->prepareErrorBagForSerialization();


        $this->handler->write($this->getId(), $this->prepareForStorage(
            $this->serialization === 'json' ? json_encode($this->attributes) : serialize($this->attributes)
        ));

        $this->started = false;
    }

    protected function prepareErrorBagForSerialization(): void
    {
        if ($this->serialization !== 'json' || $this->missing('errors')) {
            return;
        }

        $errors = [];

        foreach ($this->attributes['errors']->getBags() as $key => $value) {
            $errors[$key] = [
                'format' => $value->getFormat(),
                'messages' => $value->getMessages(),
            ];
        }

        $this->attributes['errors'] = $errors;
    }

    public function exists(array|string $key): bool
    {
        $placeholder = new stdClass;

        return !collect(is_array($key) ? $key : func_get_args())->contains(function ($key) use ($placeholder) {
            return $this->get($key, $placeholder) === $placeholder;
        });
    }

    public function missing(string|array $key): bool
    {
        return !$this->exists($key);
    }

    public function forget(array|string $keys): void
    {
        Arr::forget($this->attributes, $keys);
    }

    public function invalidate(): bool
    {
        $this->flush();

        return $this->migrate(true);
    }

    public function flush(): void
    {
        $this->attributes = [];
    }

    public function ageFlashData(): void
    {
        $this->forget($this->get('_flash.old', []));

        $this->put('_flash.old', $this->get('_flash.new', []));

        $this->put('_flash.new', []);
    }

    public function getName(): string
    {
        return $this->name;
    }


    public function isValidId(?string $id): bool
    {
        return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
    }

    protected function generateSessionId(): string
    {
        return Str::random(40);
    }


    public function start(): void
    {
        $this->loadSession();

        if (!$this->has('_token')) {
            $this->regenerateToken();
        }

        $this->started = true;
    }

    public function pull(string $key,mixed $default = null): mixed
    {
        return Arr::pull($this->attributes, $key, $default);
    }

    public function has(array|string $key): bool
    {
        return !collect(is_array($key) ? $key : func_get_args())->contains(function ($key) {
            return is_null($this->get($key));
        });
    }

    protected function loadSession(): void
    {
        $this->attributes = array_merge($this->attributes, $this->readFromHandler());

        $this->marshalErrorBag();
    }

    protected function marshalErrorBag(): void
    {
        if ($this->serialization !== 'json' || $this->missing('errors')) {
            return;
        }

        $errorBag = new ViewErrorBag();

        foreach ($this->get('errors') as $key => $value) {
            $messageBag = new MessageBag($value['messages']);

            $errorBag->put($key, $messageBag->setFormat($value['format']));
        }

        $this->put('errors', $errorBag);
    }

    protected function readFromHandler(): array
    {
        if ($data = $this->handler->read($this->getId())) {
            if ($this->serialization = 'json') {
                $data = json_decode($this->prepareForUnserialize($data), true);
            } else {
                $data = @unserialize($this->prepareForUnserialize($data));
            }

            if ($data !== false && is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    protected function prepareForUnserialize(string $data): string
    {
        return $data;
    }

    protected function prepareForStorage(string $data): string
    {
        return $data;
    }

    public function handlerNeedsRequest(): bool
    {
        return $this->handler instanceof CookieSessionHandler;
    }

    public function setRequestOnHandler(RequestInterface $request): void
    {
        if ($this->handlerNeedsRequest()) {
            $this->handler->setRequest($request);
        }
    }

    public function regenerateToken(): void
    {
        $this->put('_token', Str::random(40));
    }

    public function put(array|string $key, mixed $value): void
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            Arr::set($this->attributes, $arrayKey, $arrayValue);
        }
    }


    public function getId(): string
    {
        return $this->id;
    }

    public function flashInput(array $value): void
    {
        $this->flash('_old_input', $value);
    }

    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->put($key, $array);
    }

    public function flash(string $key, mixed $value = true): void
    {
        $this->put($key, $value);

        $this->push('_flash.new', $key);

        $this->removeFromOldFlashData([$key]);
    }

    protected function removeFromOldFlashData(array $keys): void
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    public function all(): array
    {
        return $this->attributes;
    }

    public function setPreviousUrl(string $url): void
    {
        $this->put('_previous.url', $url);
    }

    public function migrate($destroy = false): bool
    {
        if ($destroy) {
            $this->handler->destroy($this->getId());
        }

        $this->setId($this->generateSessionId());

        return true;
    }

    public function regenerate($destroy = false): bool
    {
        return tap($this->migrate($destroy), function () {
            $this->regenerateToken();
        });
    }

    public function remove(string $key): mixed
    {
        return Arr::pull($this->attributes, $key);
    }
}