<?php

namespace Framework\Kernel\Translation;

use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\NamespacedItemResolver;
use Framework\Kernel\Support\Str;
use Framework\Kernel\Translation\Contracts\FileLoaderInterface;
use Framework\Kernel\Translation\Contracts\TranslatorInterface;
use InvalidArgumentException;

class Translator extends NamespacedItemResolver implements TranslatorInterface
{
    protected ?\Closure $determineLocalesUsing = null;
    protected bool $handleMissingTranslationKeys = true;
    protected ?string $fallback = null;

    protected string $locale;

    protected array $loaded = [];

    public function __construct(
        protected FileLoaderInterface $loader,
        string $locale,
    )
    {
        $this->setLocale($locale);
    }

    public function setFallback(string $fallback): static
    {
        $this->fallback = $fallback;

        return $this;
    }

    public function setLocale(string $locale): static
    {
        if (Str::contains($locale, ['/', '\\'])) {
            throw new InvalidArgumentException('Invalid characters present in locale.');
        }

        $this->locale = $locale;

        return $this;
    }

    public function get(string $key, array $replace = [], ?string $locale = null, bool $fallback = false): array|string
    {
        $locale = $locale ?: $this->locale;

        $this->load('*', '*', $locale);

        $line = $this->loaded['*']['*'][$locale][$key] ?? null;

        if (! isset($line)) {
            [$namespace, $group, $item] = $this->parseKey($key);

            $locales = $fallback ? $this->localeArray($locale) : [$locale];

            foreach ($locales as $languageLineLocale) {
                if (! is_null($line = $this->getLine(
                    $namespace, $group, $languageLineLocale, $item, $replace
                ))) {
                    return $line;
                }
            }

            $key = $this->handleMissingTranslationKey(
                $key, $replace, $locale, $fallback
            );
        }


        return $this->makeReplacements($line ?: $key, $replace);
    }

    protected function handleMissingTranslationKey(string $key,array $replace,?string $locale,bool $fallback): string
    {
        if (! $this->handleMissingTranslationKeys ||
            ! isset($this->missingTranslationKeyCallback)) {
            return $key;
        }

        $this->handleMissingTranslationKeys = false;

        $key = call_user_func(
            $this->missingTranslationKeyCallback,
            $key, $replace, $locale, $fallback
        ) ?? $key;

        $this->handleMissingTranslationKeys = true;

        return $key;
    }

    protected function getLine(string $namespace,string $group,string $locale,?string $item, array $replace): array|string|null
    {
        $this->load($namespace, $group, $locale);

        $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);

        if (is_string($line)) {
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            array_walk_recursive($line, function (&$value, $key) use ($replace) {
                $value = $this->makeReplacements($value, $replace);
            });

            return $line;
        }

        return null;
    }

    protected function makeReplacements(string $line, array $replace): string
    {
        if (empty($replace)) {
            return $line;
        }

        $shouldReplace = [];

        foreach ($replace as $key => $value) {
            if (is_object($value) && isset($this->stringableHandlers[get_class($value)])) {
                $value = call_user_func($this->stringableHandlers[get_class($value)], $value);
            }

            $shouldReplace[':'.Str::ucfirst($key ?? '')] = Str::ucfirst($value ?? '');
            $shouldReplace[':'.Str::upper($key ?? '')] = Str::upper($value ?? '');
            $shouldReplace[':'.$key] = $value;
        }

        return strtr($line, $shouldReplace);
    }

    protected function localeArray(?string $locale): array
    {
        $locales = array_filter([$locale ?: $this->locale, $this->fallback]);

        return call_user_func($this->determineLocalesUsing ?: fn () => $locales, $locales);
    }


    public function parseKey(string $key): array
    {
        $segments = parent::parseKey($key);

        if (is_null($segments[0])) {
            $segments[0] = '*';
        }

        return $segments;
    }

    public function load(string $namespace,string $group,string $locale): void
    {
        if ($this->isLoaded($namespace, $group, $locale)) {
            return;
        }

        $lines = $this->loader->load($locale, $group, $namespace);

        $this->loaded[$namespace][$group][$locale] = $lines;
    }

    protected function isLoaded(string $namespace,string $group,string $locale): bool
    {
        return isset($this->loaded[$namespace][$group][$locale]);
    }
}