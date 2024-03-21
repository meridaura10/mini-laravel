<?php

namespace Framework\Kernel\Console\Input;

class InputArgument
{
    public const REQUIRED = 1;

    public const OPTIONAL = 2;

    public const IS_ARRAY = 4;

    public function __construct(
        private string $name,
        private ?int $mode = null,
        private string $description = '',
        private string|bool|int|float|array|null $default = null,
        private \Closure|array $suggestedValues = []
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isArray(): bool
    {
        return self::IS_ARRAY === (self::IS_ARRAY & $this->mode);
    }
}
