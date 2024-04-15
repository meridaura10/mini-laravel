<?php

namespace Framework\Kernel\Validator\Contracts;

interface ValidationFactoryInterface
{
    public function make(array $data, array $rules, array $messages = [], array $attributes = []): ValidatorInterface;
}