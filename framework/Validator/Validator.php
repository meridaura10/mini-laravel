<?php

namespace Framework\Kernel\Validator;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Filesystem\File;
use Framework\Kernel\Filesystem\UploadedFile;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Support\Str;
use Framework\Kernel\Translation\Contracts\TranslatorInterface;
use Framework\Kernel\Validator\Bags\MessageBag;
use Framework\Kernel\Validator\Contracts\DatabasePresenceVerifierInterface;
use Framework\Kernel\Validator\Contracts\PresenceVerifierInterface;
use Framework\Kernel\Validator\Contracts\ValidatorInterface;
use Framework\Kernel\Validator\Exceptions\ValidationException;
use Framework\Kernel\Validator\Rules\ImplicitRule;
use Framework\Kernel\Validator\Rules\ValidationRuleParser;
use Framework\Kernel\Validator\Traits\FormatsMessagesTrait;
use Framework\Kernel\Validator\Traits\ValidatesAttributesTrait;

class Validator implements ValidatorInterface
{
    use ValidatesAttributesTrait,
        FormatsMessagesTrait;

    protected ?ApplicationInterface $app = null;

    protected ?PresenceVerifierInterface $presenceVerifier = null;

    protected bool $stopOnFirstFailure = false;

    public bool $excludeUnvalidatedArrayKeys = false;

    protected string $dotPlaceholder;

    protected array $after = [];

    protected array $data = [];

    protected array $rules = [];

    public array $fallbackMessages = [];

    protected array $initialRules = [];

    protected ?MessageBag $messages = null;

    protected array $distinctValues = [];

    protected array $implicitAttributes = [];

    protected array $excludeAttributes = [];

    protected array $failedRules = [];

    public array $replacers = [];

    protected ?Closure $implicitAttributesFormatter = null;

    protected string $exception = ValidationException::class;

    protected array $implicitRules = [
        'Accepted',
        'AcceptedIf',
        'Declined',
        'DeclinedIf',
        'Filled',
        'Missing',
        'MissingIf',
        'MissingUnless',
        'MissingWith',
        'MissingWithAll',
        'Present',
        'PresentIf',
        'PresentUnless',
        'PresentWith',
        'PresentWithAll',
        'Required',
        'RequiredIf',
        'RequiredIfAccepted',
        'RequiredUnless',
        'RequiredWith',
        'RequiredWithAll',
        'RequiredWithout',
        'RequiredWithoutAll',
    ];

    protected array $fileRules = [
        'Between',
        'Dimensions',
        'Extensions',
        'File',
        'Image',
        'Max',
        'Mimes',
        'Mimetypes',
        'Min',
        'Size',
    ];

    protected array $dependentRules = [
        'After',
        'AfterOrEqual',
        'Before',
        'BeforeOrEqual',
        'Confirmed',
        'Different',
        'ExcludeIf',
        'ExcludeUnless',
        'ExcludeWith',
        'ExcludeWithout',
        'Gt',
        'Gte',
        'Lt',
        'Lte',
        'AcceptedIf',
        'DeclinedIf',
        'RequiredIf',
        'RequiredIfAccepted',
        'RequiredUnless',
        'RequiredWith',
        'RequiredWithAll',
        'RequiredWithout',
        'RequiredWithoutAll',
        'PresentIf',
        'PresentUnless',
        'PresentWith',
        'PresentWithAll',
        'Prohibited',
        'ProhibitedIf',
        'ProhibitedUnless',
        'Prohibits',
        'MissingIf',
        'MissingUnless',
        'MissingWith',
        'MissingWithAll',
        'Same',
        'Unique',
    ];

    protected array $excludeRules = ['Exclude', 'ExcludeIf', 'ExcludeUnless', 'ExcludeWith', 'ExcludeWithout'];

    protected array $sizeRules = ['Size', 'Between', 'Min', 'Max', 'Gt', 'Lt', 'Gte', 'Lte'];

    protected array $numericRules = ['Numeric', 'Integer', 'Decimal'];

    protected array $defaultNumericRules = ['Numeric', 'Integer', 'Decimal'];

    protected ?string $currentRule = null;

    public function __construct(
        protected TranslatorInterface $translator,
        array $data,
        array $rules,
        protected array $customMessages,
        protected array $customAttributes,

    )
    {
        $this->dotPlaceholder = Str::random();
        $this->data = $this->parseData($data);

        $this->setRules($rules);
    }

    protected function parseData(array $data): array
    {
        $newData = [];

        foreach ($data as $key => $value){
            if(is_array($value)){
                $this->parseData($value);
            }

            $key = str_replace(
                ['.', '*'],
                [$this->dotPlaceholder, '__asterisk__'],
                $key,
            );


            $newData[$key] = $value;
        }

        return $newData;
    }

    public function setRules(array $rules): static
    {
        $rules = collect($rules)->mapWithKeys(function ($value, $key) {
            return [str_replace('\.', $this->dotPlaceholder, $key) => $value];
        })->toArray();

        $this->initialRules = $rules;

        $this->rules = [];

        $this->addRules($rules);

        return $this;
    }

    protected function addRules(array $rules): void
    {
        $response = (new ValidationRuleParser($this->data))
            ->explode(ValidationRuleParser::filterConditionalRules($rules, $this->data));

        $this->rules = array_merge_recursive(
            $this->rules, $response->rules
        );

        $this->implicitAttributes = array_merge(
            $this->implicitAttributes, $response->implicitAttributes
        );
    }

    public function validated(): array
    {

    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    public function passes(): bool
    {
        $this->messages = new MessageBag();

        [$this->distinctValues, $this->failedRules] = [[], []];

        foreach ($this->rules as $attribute => $rules){
            if ($this->shouldBeExcluded($attribute)) {
                $this->removeAttribute($attribute);

                continue;
            }

            if ($this->stopOnFirstFailure && $this->messages->isNotEmpty()) {
                break;
            }

            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);

                if ($this->shouldBeExcluded($attribute)) {
                    break;
                }

                if ($this->shouldStopValidating($attribute)) {
                    break;
                }
            }
        }

        foreach ($this->after as $after) {
            $after();
        }

        return $this->messages->isEmpty();
    }

    protected function shouldStopValidating(string $attribute): bool
    {
        $cleanedAttribute = $this->replacePlaceholderInString($attribute);

        if ($this->hasRule($attribute, ['Bail'])) {
            return $this->messages->has($cleanedAttribute);
        }

        if (isset($this->failedRules[$cleanedAttribute]) &&
            array_key_exists('uploaded', $this->failedRules[$cleanedAttribute])) {
            return true;
        }

        return $this->hasRule($attribute, $this->implicitRules) &&
            isset($this->failedRules[$cleanedAttribute]) &&
            array_intersect(array_keys($this->failedRules[$cleanedAttribute]), $this->implicitRules);
    }

    protected function replacePlaceholderInString(string $value): string
    {
        return str_replace(
            [$this->dotPlaceholder, '__asterisk__'],
            ['.', '*'],
            $value
        );
    }

    public function hasRule(string $attribute,array|string $rules): bool
    {
        return ! is_null($this->getRule($attribute, $rules));
    }

    protected function getRule(string $attribute,array|string $rules): ?array
    {
        if (! array_key_exists($attribute, $this->rules)) {
            return null;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            [$rule, $parameters] = ValidationRuleParser::parse($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }

        return null;
    }

    protected function validateAttribute(string $attribute,string $rule): void
    {
        $this->currentRule = $rule;

        [$rule, $parameters] = ValidationRuleParser::parse($rule);

        if ($rule === '') {
            return;
        }

        if($this->dependsOnOtherFields($rule)){

        }

        $value = $this->getValue($attribute);

        $validatable = $this->isValidatable($rule, $attribute, $value);

        $method = "validate{$rule}";

        $this->numericRules = $this->defaultNumericRules;

        if ($validatable && ! $this->$method($attribute, $value, $parameters, $this)) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    public function addFailure(string $attribute,string $rule,array $parameters = []): void
    {
        if (! $this->messages) {
            $this->passes();
        }

        $attributeWithPlaceholders = $attribute;

        $attribute = $this->replacePlaceholderInString($attribute);

        if (in_array($rule, $this->excludeRules)) {
             $this->excludeAttribute($attribute);
             return;
        }

        $this->messages->add($attribute, $this->makeReplacements(
            $this->getMessage($attributeWithPlaceholders, $rule), $attribute, $rule, $parameters
        ));

        $this->failedRules[$attribute][$rule] = $parameters;
    }



    protected function getFromLocalArray(string $attribute,string $lowerRule,?array $source = null): null|string|array
    {
        $source = $source ?: $this->customMessages;

        $keys = ["{$attribute}.{$lowerRule}", $lowerRule, $attribute];

        foreach ($keys as $key) {
            foreach (array_keys($source) as $sourceKey) {
                if (str_contains($sourceKey, '*')) {
                    $pattern = str_replace('\*', '([^.]*)', preg_quote($sourceKey, '#'));

                    if (preg_match('#^'.$pattern.'\z#u', $key) === 1) {
                        $message = $source[$sourceKey];

                        if (is_array($message) && isset($message[$lowerRule])) {
                            return $message[$lowerRule];
                        }

                        return $message;
                    }

                    continue;
                }

                if (Str::is($sourceKey, $key)) {
                    $message = $source[$sourceKey];

                    if ($sourceKey === $attribute && is_array($message) && isset($message[$lowerRule])) {
                        return $message[$lowerRule];
                    }

                    return $message;
                }
            }
        }

        return null;
    }

    protected function excludeAttribute(string $attribute): void
    {
        $this->excludeAttributes[] = $attribute;

        $this->excludeAttributes = array_unique($this->excludeAttributes);
    }

    protected function isValidatable(object|string $rule,string $attribute,mixed $value): bool
    {
        if (in_array($rule, $this->excludeRules)) {
            return true;
        }

        return
            $this->isNotNullIfMarkedAsNullable($rule, $attribute) &&
            $this->hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute);
    }
    protected function isImplicit(object|string $rule): bool
    {
        return $rule instanceof ImplicitRule ||
            in_array($rule, $this->implicitRules);
    }


    protected function isNotNullIfMarkedAsNullable(string $rule,string $attribute): bool
    {
        if ($this->isImplicit($rule) || ! $this->hasRule($attribute, ['Nullable'])) {
            return true;
        }

        return ! is_null(Arr::get($this->data, $attribute, 0));
    }

    protected function hasNotFailedPreviousRuleIfPresenceRule(string|object $rule,string $attribute): bool
    {
        return !in_array($rule, ['Unique', 'Exists']) || !$this->messages->has($attribute);
    }

    public function getValue(string $attribute): mixed
    {
        return Arr::get($this->data, $attribute);
    }

    protected function dependsOnOtherFields(string $rule): bool
    {
        return in_array($rule, $this->dependentRules);
    }

    protected function removeAttribute(string $attribute): void
    {
        Arr::forget($this->data, $attribute);
        Arr::forget($this->rules, $attribute);
    }

    protected function shouldBeExcluded(string $attribute): bool
    {
        foreach ($this->excludeAttributes as $excludeAttribute) {
            if ($attribute === $excludeAttribute ||
                Str::startsWith($attribute, $excludeAttribute.'.')) {
                return true;
            }
        }

        return false;
    }

    public function messages(): MessageBag
    {
        if (! $this->messages) {
            $this->passes();
        }

        return $this->messages;
    }

    public function errors(): MessageBag
    {
        return $this->messages();
    }

    public function getException(): string
    {
        return $this->exception;
    }


    public function stopOnFirstFailure(bool $stopOnFirstFailure = true): static
    {
        $this->stopOnFirstFailure = $stopOnFirstFailure;

        return $this;
    }

    public function setPresenceVerifier(?PresenceVerifierInterface $verifier): static
    {
        $this->presenceVerifier = $verifier;

        return $this;
    }

    public function setContainer(?ApplicationInterface $application): static
    {
        $this->app = $application;

        return $this;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getPresenceVerifier(?string $connection = null): PresenceVerifierInterface
    {
        if (! isset($this->presenceVerifier)) {
            throw new \RuntimeException('Presence verifier has not been set.');
        }

        if ($this->presenceVerifier instanceof DatabasePresenceVerifierInterface) {
            $this->presenceVerifier->setConnection($connection);
        }

        return $this->presenceVerifier;
    }
}