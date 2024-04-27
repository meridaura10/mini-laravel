<?php

namespace Framework\Kernel\Http\Requests;

use Framework\Kernel\Http\Requests\Contracts\ValidatesWhenResolvedInterface;
use Framework\Kernel\Http\Requests\Traits\ValidatesWhenResolvedTrait;
use Framework\Kernel\Route\Redirector\Contracts\RedirectorInterface;
use Framework\Kernel\Validator\Contracts\ValidationFactoryInterface;
use Framework\Kernel\Validator\Contracts\ValidatorInterface;
use Framework\Kernel\Validator\ValidationFactory;

class FormRequest extends Request implements ValidatesWhenResolvedInterface
{
    use ValidatesWhenResolvedTrait;


    protected ?ValidatorInterface $validator = null;

    protected bool $stopOnFirstFailure = false;

    protected ?string $redirect = null;

    protected string $errorBag = 'default';

    protected ?RedirectorInterface $redirector = null;

    protected ?string $redirectRoute = null;

    public function validated(array|int|null|string $key = null,mixed $default = null): mixed
    {
        return data_get($this->validator->validated(), $key, $default);
    }

    protected function failedValidation(ValidatorInterface $validator)
    {
        $exception = $validator->getException();

        throw (new $exception($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    protected function getRedirectUrl(): string
    {
        $url = $this->redirector->getUrlGenerator();

        if ($this->redirect) {
            return $url->to($this->redirect);
        } elseif ($this->redirectRoute) {
            return $url->route($this->redirectRoute);
        }

        return $url->previous();
    }

    protected function getValidatorInstance(): ValidatorInterface
    {
        if($this->validator){
            return $this->validator;
        }

        $factory = $this->app->make(ValidationFactoryInterface::class);


        if(method_exists($this,'validator')){
            $validator = $this->app->call([$this,'validator'],compact('factory'));
        }else{
            $validator = $this->createDefaultValidator($factory);
        }

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        if (method_exists($this, 'after')) { // реалізувати як тільки так одразу
            $validator->after($this->app->call(
                $this->after(...),
                ['validator' => $validator]
            ));
        }

        $this->setValidator($validator);

        return $validator;
    }


    protected function createDefaultValidator(ValidationFactoryInterface $factory): ValidatorInterface
    {
        return $factory->make(
            $this->validationData(),
            $this->validationRules(),
            $this->messages(),
            $this->attributes(),
        )->stopOnFirstFailure($this->stopOnFirstFailure);
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    protected function validationData(): array
    {
        return $this->all();
    }

    public function setRedirector(RedirectorInterface $redirector): static
    {
        $this->redirector = $redirector;

        return $this;
    }

    protected function validationRules(): array
    {
        return method_exists($this, 'rules') ? $this->app->call([$this, 'rules']) : [];
    }

    public function setValidator(ValidatorInterface $validator): static
    {
        $this->validator = $validator;

        return $this;
    }
}
