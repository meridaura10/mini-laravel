<?php

namespace Framework\Kernel\Validator\Bags;

use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\MessageProviderInterface;
use Framework\Kernel\Support\Str;

class MessageBag implements MessageProviderInterface, Arrayable
{
    protected array $messages = [];

    protected string $format = ':message';

    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $value) {
            $value = $value instanceof Arrayable ? $value->toArray() : (array) $value;

            $this->messages[$key] = array_unique($value);
        }
    }

    public function isNotEmpty(): bool
    {
        return $this->any();
    }

    public function any(): bool
    {
        return $this->count() > 0;
    }

    public function count(): int
    {
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }

    public function isEmpty(): bool
    {
        return ! $this->any();
    }

    public function add(string $key,string $message): static
    {
        if ($this->isUnique($key, $message)) {
            $this->messages[$key][] = $message;
        }

        return $this;
    }

    public function messages(): array
    {
        return $this->messages;
    }

    protected function isUnique(string $key,string $message): bool
    {
        $messages = (array) $this->messages;

        return ! isset($messages[$key]) || ! in_array($message, $messages[$key]);
    }

    public function has(null|array|string $key = null): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        if (is_null($key)) {
            return $this->any();
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $key) {
            if ($this->first($key) === '') {
                return false;
            }
        }

        return true;
    }

    public function first(?string $key = null,?string $format = null)
    {
        $messages = is_null($key) ? $this->all($format) : $this->get($key, $format);

        $firstMessage = Arr::first($messages, null, '');

        return is_array($firstMessage) ? Arr::first($firstMessage) : $firstMessage;
    }

    public function get(string $key,?string $format = null): array
    {
        if (array_key_exists($key, $this->messages)) {
            return $this->transform(
                $this->messages[$key], $this->checkFormat($format), $key
            );
        }

        if (str_contains($key, '*')) {
            return $this->getMessagesForWildcardKey($key, $format);
        }
    }

    protected function getMessagesForWildcardKey($key, $format): array
    {
        return collect($this->messages)
            ->filter(function ($messages, $messageKey) use ($key) {
                return Str::is($key, $messageKey);
            })
            ->map(function ($messages, $messageKey) use ($format) {
                return $this->transform(
                    $messages, $this->checkFormat($format), $messageKey
                );
            })->all();
    }

    protected function transform($messages, $format, $messageKey): array
    {
        if ($format == ':message') {
            return (array) $messages;
        }

        return collect((array) $messages)
            ->map(function ($message) use ($format, $messageKey) {
                return str_replace([':message', ':key'], [$message, $messageKey], $format);
            })->all();
    }

    public function all($format = null): array
    {
        $format = $this->checkFormat($format);

        $all = [];

        foreach ($this->messages as $key => $messages) {
            $all = array_merge($all, $this->transform($messages, $format, $key));
        }

        return $all;
    }

    protected function checkFormat($format)
    {
        return $format ?: $this->format;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getMessages(): array
    {
        return $this->messages();
    }

    public function setFormat(string $format = ':message'): static
    {
        $this->format = $format;

        return $this;
    }

    public function getMessageBag(): MessageBag
    {
        return $this;
    }

    public function toArray(): array
    {
        return $this->getMessages();
    }

}