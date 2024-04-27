<?php

namespace Framework\Kernel\Validator\Traits;

use Brick\Math\BigNumber;
use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Filesystem\File;
use Framework\Kernel\Filesystem\UploadedFile;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Exceptions\MathException;
use Framework\Kernel\Support\Str;
use Framework\Kernel\Validator\Contracts\DatabasePresenceVerifierInterface;
use Framework\Kernel\Validator\Contracts\PresenceVerifierInterface;

trait ValidatesAttributesTrait
{
    public function validateString(string $attribute,mixed $value): bool
    {
        return is_string($value);
    }

    public function validateMax(string $attribute,mixed $value,array $parameters): bool
    {
        $this->requireParameterCount(1, $parameters, 'max');

        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return BigNumber::of($this->getSize($attribute, $value))->isLessThanOrEqualTo($this->trim($parameters[0]));
    }

    public function validateNullable(): bool
    {
        return true;
    }

    public function validateRequired(string $attribute,mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_countable($value) && count($value) < 1) {
            return false;
        } elseif ($value instanceof File) {
            return (string) $value->getPath() !== '';
        }

        return true;
    }

    /**
     * Parse the connection / table for the unique / exists rules.
     *
     * @param  string  $table
     * @return array
     */
    public function parseTable(string $table): array
    {
        [$connection, $table] = str_contains($table, '.') ? explode('.', $table, 2) : [null, $table];

        if (str_contains($table, '\\') && class_exists($table) && is_a($table, Model::class, true)) {
            $model = new $table;

            $table = $model->getTable();
            $connection ??= $model->getConnectionName();

            if (str_contains($table, '.') && Str::startsWith($table, $connection)) {
                $connection = null;
            }

            $idColumn = $model->getKeyName();
        }

        return [$connection, $table, $idColumn ?? null];
    }

    public function validateBoolean(string $attribute,mixed $value): bool
    {
        $acceptable = [true, false, 0, 1, '0', '1'];

        return in_array($value, $acceptable, true);
    }

    public function validateExists(string $attribute,mixed $value,array $parameters): bool
    {
        $this->requireParameterCount(1, $parameters, 'exists');

        [$connection, $table] = $this->parseTable($parameters[0]);

        $column = $this->getQueryColumn($parameters, $attribute);

        $expected = is_array($value) ? count(array_unique($value)) : 1;

        if ($expected === 0) {
            return true;
        }

        return $this->getExistCount(
                $connection, $table, $column, $value, $parameters
            ) >= $expected;
    }

    protected function getExistCount(mixed $connection,string $table,string $column,mixed $value,array $parameters): int
    {
        $verifier = $this->getPresenceVerifier($connection);

        $extra = $this->getExtraConditions(
            array_values(array_slice($parameters, 2))
        );

        return is_array($value)
            ? $verifier->getMultiCount($table, $column, $value, $extra)
            : $verifier->getCount($table, $column, $value, null, null, $extra);
    }

    protected function getExtraConditions(array $segments): array
    {
        $extra = [];

        $count = count($segments);

        for ($i = 0; $i < $count; $i += 2) {
            $extra[$segments[$i]] = $segments[$i + 1];
        }

        return $extra;
    }

    public function getQueryColumn(array $parameters,string $attribute): string
    {
        return isset($parameters[1]) && $parameters[1] !== 'NULL'
            ? $parameters[1] : $this->guessColumnForQuery($attribute);
    }

    public function guessColumnForQuery($attribute)
    {
        if (in_array($attribute, Arr::collapse($this->implicitAttributes))
            && ! is_numeric($last = last(explode('.', $attribute)))) {
            return $last;
        }

        return $attribute;
    }


    public function validateMin(string $attribute,mixed $value,array $parameters): bool
    {
        $this->requireParameterCount(1, $parameters, 'min');

        return BigNumber::of($this->getSize($attribute, $value))->isGreaterThanOrEqualTo($this->trim($parameters[0]));
    }

    public function getSize(string $attribute, mixed $value): float|int|string
    {
        $hasNumeric = $this->hasRule($attribute, $this->numericRules);

        if (is_numeric($value) && $hasNumeric) {
            return $this->ensureExponentWithinAllowedRange($attribute, $this->trim($value));
        } elseif (is_array($value)) {
            return count($value);
        } elseif ($value instanceof File) {
            return $value->getSize() / 1024;
        }

        return mb_strlen($value ?? '');
    }

    protected function ensureExponentWithinAllowedRange(string $attribute,mixed $value): mixed
    {
        $stringValue = (string) $value;

        if(! is_numeric($value) || ! Str::contains($stringValue,'e',ignoreCase: true)){
            return $value;
        };

        $scale = (int) (Str::contains($stringValue, 'e')
            ? Str::after($stringValue, 'e')
            : Str::after($stringValue, 'E'));

        $withinRange = (
            $this->ensureExponentWithinAllowedRangeUsing ?? fn ($scale) => $scale <= 1000 && $scale >= -1000
        )($scale, $attribute, $value);

        if (! $withinRange) {
            throw new MathException('Scientific notation exponent outside of allowed range.');
        }

        return $value;

    }

    protected function trim(mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }

    public function validateInteger(string $attribute,mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function requireParameterCount(int $count,array $parameters,string $rule): void
    {
        if (count($parameters) < $count) {
            throw new \InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
        }
    }
}