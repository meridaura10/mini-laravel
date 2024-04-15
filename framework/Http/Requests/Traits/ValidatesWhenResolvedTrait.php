<?php

namespace Framework\Kernel\Http\Requests\Traits;

use Framework\Kernel\Validator\Exceptions\ValidationException;

trait ValidatesWhenResolvedTrait
{
    public function validateResolved(): void
    {
        $this->prepareForValidation();

        $instance = $this->getValidatorInstance();

        if ($instance->fails()) {
            $this->failedValidation($instance);
        }

        $this->passedValidation();
    }

    protected function prepareForValidation(): void
    {
        //
    }

    protected function passedValidation(): void
    {
        //
    }
}