<?php

namespace Framework\Kernel\Validator;

use Closure;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Translation\Contracts\TranslatorInterface;
use Framework\Kernel\Translation\Translator;
use Framework\Kernel\Validator\Contracts\PresenceVerifierInterface;
use Framework\Kernel\Validator\Contracts\ValidationFactoryInterface;
use Framework\Kernel\Validator\Contracts\ValidatorInterface;

class ValidationFactory implements ValidationFactoryInterface
{
    protected bool $excludeUnvalidatedArrayKeys = true;
    protected ?PresenceVerifierInterface $verifier = null;

    protected ?Closure $resolver = null;

    public function __construct(
        protected TranslatorInterface $translator,
        protected ?ApplicationInterface $app = null,
    ) {
        //
    }

    public function make(array $data, array $rules, array $messages = [], array $attributes = []): ValidatorInterface
    {
        $validator = $this->resolve(
            $data,$rules,$messages,$attributes,
        );

        if($this->verifier){
            $validator->setPresenceVerifier($this->verifier);
        }

        if ($this->app) {
            $validator->setContainer($this->app);
        }

        $validator->excludeUnvalidatedArrayKeys = $this->excludeUnvalidatedArrayKeys;

        return $validator;
    }

    protected function resolve(array $data, array $rules, array $messages, array $attributes): ValidatorInterface
    {
        if($this->resolver){
            return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $attributes);
        }

        return new Validator($this->translator, $data, $rules, $messages, $attributes);
    }

    public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier): void
    {
        $this->verifier = $presenceVerifier;
    }
}