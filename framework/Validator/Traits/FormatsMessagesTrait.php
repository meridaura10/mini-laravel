<?php

namespace Framework\Kernel\Validator\Traits;

use Closure;
use Framework\Kernel\Filesystem\File;
use Framework\Kernel\Filesystem\UploadedFile;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Str;
use Framework\Kernel\Validator\Contracts\ValidatorInterface;

trait FormatsMessagesTrait
{
    use ReplacesAttributesTrait;

    public function makeReplacements($message, $attribute, $rule, $parameters): string
    {
        $message = $this->replaceAttributePlaceholder(
            $message, $this->getDisplayableAttribute($attribute)
        );

        $message = $this->replaceInputPlaceholder($message, $attribute);
        $message = $this->replaceIndexPlaceholder($message, $attribute);
        $message = $this->replacePositionPlaceholder($message, $attribute);

        if (isset($this->replacers[Str::snake($rule)])) {
            return $this->callReplacer($message, $attribute, Str::snake($rule), $parameters, $this);
        } elseif (method_exists($this, $replacer = "replace{$rule}")) {
            return $this->$replacer($message, $attribute, $rule, $parameters);
        }

        return $message;
    }


    protected function callReplacer(string $message,string $attribute,string $rule,array $parameters,ValidatorInterface $validator): ?string
    {
        $callback = $this->replacers[$rule];

        if ($callback instanceof Closure) {
            return $callback(...func_get_args());
        } elseif (is_string($callback)) {
            return $this->callClassBasedReplacer($callback, $message, $attribute, $rule, $parameters, $validator);
        }
    }

    protected function replacePositionPlaceholder(string $message,string $attribute): string
    {
        return $this->replaceIndexOrPositionPlaceholder(
            $message, $attribute, 'position', fn ($segment) => $segment + 1
        );
    }

    protected function replaceIndexPlaceholder(string $message,string $attribute): string
    {
        return $this->replaceIndexOrPositionPlaceholder(
            $message, $attribute, 'index'
        );
    }

    protected function replaceIndexOrPositionPlaceholder(string $message,string $attribute,string $placeholder, ?Closure $modifier = null): string
    {
        $segments = explode('.', $attribute);

        $modifier ??= fn ($value) => $value;

        $numericIndex = 1;

        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                if ($numericIndex === 1) {
                    $message = str_ireplace(':'.$placeholder, $modifier((int) $segment), $message);
                }

                $message = str_ireplace(
                    ':'.$this->numberToIndexOrPositionWord($numericIndex).'-'.$placeholder,
                    $modifier((int) $segment),
                    $message
                );

                $numericIndex++;
            }
        }

        return $message;
    }

    protected function numberToIndexOrPositionWord(int $value): string
    {
        return [
            1 => 'first',
            2 => 'second',
            3 => 'third',
            4 => 'fourth',
            5 => 'fifth',
            6 => 'sixth',
            7 => 'seventh',
            8 => 'eighth',
            9 => 'ninth',
            10 => 'tenth',
        ][(int) $value] ?? 'other';
    }

    protected function replaceInputPlaceholder(string $message,string $attribute): string
    {
        $actualValue = $this->getValue($attribute);

        if (is_scalar($actualValue) || is_null($actualValue)) {
            $message = str_replace(':input', $this->getDisplayableValue($attribute, $actualValue), $message);
        }

        return $message;
    }

    public function getDisplayableValue($attribute, $value)
    {
        if (isset($this->customValues[$attribute][$value])) {
            return $this->customValues[$attribute][$value];
        }

        if (is_array($value)) {
            return 'array';
        }

        $key = "validation.values.{$attribute}.{$value}";

        if (($line = $this->translator->get($key)) !== $key) {
            return $line;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'empty';
        }

        return (string) $value;
    }

    public function getDisplayableAttribute(string $attribute): string
    {
        $primaryAttribute = $this->getPrimaryAttribute($attribute);

        $expectedAttributes = $attribute != $primaryAttribute
            ? [$attribute, $primaryAttribute] : [$attribute];

        foreach ($expectedAttributes as $name){
            if (isset($this->customAttributes[$name])) {
                return $this->customAttributes[$name];
            }

            if ($line = $this->getAttributeFromTranslations($name)) {
                return $line;
            }
        }

        if (isset($this->implicitAttributes[$primaryAttribute])) {
            return ($formatter = $this->implicitAttributesFormatter)
                ? $formatter($attribute)
                : $attribute;
        }

        return str_replace('_', ' ', Str::snake($attribute));
    }

    protected function getAttributeFromTranslations(string $name): ?string
    {
        return Arr::get($this->translator->get('validation.attributes'), $name);
    }

    protected function getPrimaryAttribute(string $attribute): string
    {
        foreach ($this->implicitAttributes as $unparsed => $parsed) {
            if (in_array($attribute, $parsed, true)) {
                return $unparsed;
            }
        }

        return $attribute;
    }


    protected function replaceAttributePlaceholder(string $message,string $value): string
    {
        return str_replace(
            [':attribute', ':ATTRIBUTE', ':Attribute'],
            [$value, Str::upper($value), Str::ucfirst($value)],
            $message
        );
    }

    protected function getMessage(string $attribute,string $rule): string
    {
        $attributeWithPlaceholders = $attribute;

        $attribute = $this->replacePlaceholderInString($attribute);

        $inlineMessage = $this->getInlineMessage($attribute, $rule);

        if($inlineMessage){
            return $inlineMessage;
        }

        $lowerRule = Str::snake($rule);

        $customKey = "validation.custom.{$attribute}.{$lowerRule}";


        $customMessage = $this->getCustomMessageFromTranslator(
            in_array($rule, $this->sizeRules)
                ? [$customKey.".{$this->getAttributeType($attribute)}", $customKey]
                : $customKey
        );

        if ($customMessage !== $customKey) {
            return $customMessage;
        }elseif (in_array($rule, $this->sizeRules)) {
            return $this->getSizeMessage($attributeWithPlaceholders, $rule);
        }

        $key = "validation.{$lowerRule}";

        if ($key !== ($value = $this->translator->get($key))) {
            return $value;
        }

        return $this->getFromLocalArray(
            $attribute, $lowerRule, $this->fallbackMessages
        ) ?: $key;
    }

    protected function getSizeMessage(string $attribute,string $rule): string
    {
        $lowerRule = Str::snake($rule);

        $type = $this->getAttributeType($attribute);

        $key = "validation.{$lowerRule}.{$type}";

        return $this->translator->get($key);
    }

    protected function getCustomMessageFromTranslator(array|string $keys): string
    {
        foreach (Arr::wrap($keys) as $key) {
            if (($message = $this->translator->get($key)) !== $key) {
                return $message;
            }

            $shortKey = preg_replace(
                '/^validation\.custom\./', '', $key
            );

            $message = $this->getWildcardCustomMessages(Arr::dot(
                (array) $this->translator->get('validation.custom')
            ), $shortKey, $key);

            if ($message !== $key) {
                return $message;
            }
        }

        return Arr::last(Arr::wrap($keys));
    }

    protected function getWildcardCustomMessages($messages, $search, $default)
    {
        foreach ($messages as $key => $message) {
            if ($search === $key || (Str::contains($key, ['*']) && Str::is($key, $search))) {
                return $message;
            }
        }

        return $default;
    }


    protected function getInlineMessage($attribute, $rule)
    {
        $inlineEntry = $this->getFromLocalArray($attribute, Str::snake($rule));

        return is_array($inlineEntry) && in_array($rule, $this->sizeRules)
            ? $inlineEntry[$this->getAttributeType($attribute)]
            : $inlineEntry;
    }

    protected function getAttributeType(string $attribute): string
    {
        return match (true) {
            $this->hasRule($attribute, $this->numericRules) => 'numeric',
            $this->hasRule($attribute, ['Array']) => 'array',
            $this->getValue($attribute) instanceof UploadedFile,
                $this->getValue($attribute) instanceof File => 'file',
            default => 'string',
        };
    }

    protected function callClassBasedReplacer(string $callback,string $message,string $attribute,string $rule,array $parameters,ValidatorInterface $validator): string
    {
        [$class, $method] = Str::parseCallback($callback, 'replace');

        return $this->app->make($class)->{$method}(...array_slice(func_get_args(), 1));
    }
}