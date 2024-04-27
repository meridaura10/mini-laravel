<?php

namespace Framework\Kernel\Validator\Exceptions;

use Framework\Kernel\Facades\Services\Validator;
use Framework\Kernel\Http\Responses\Response;
use Framework\Kernel\Support\Arr;
use Framework\Kernel\Validator\Contracts\ValidatorInterface;

class ValidationException extends \Exception
{
    public ?string $redirectTo = null;

    public int $status = 422;
    public function __construct(
       protected ValidatorInterface $validator,
       public ?Response $response = null,
       public  string $errorBag = 'default'
    ) {
        parent::__construct(static::summarize($validator));
    }

    public static function withMessages(array $messages): static
    {
        return new static(tap(Validator::make([], []), function ($validator) use ($messages) {
            foreach ($messages as $key => $value) {
                foreach (Arr::wrap($value) as $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        }));
    }

    protected static function summarize(ValidatorInterface $validator): string
    {
        $messages = $validator->errors()->all();

        if (! count($messages) || ! is_string($messages[0])) {
            return $validator->getTranslator()->get('The given data was invalid.');
        }

        $message = array_shift($messages);

        if ($count = count($messages)) {
            $pluralized = $count === 1 ? 'error' : 'errors';

            $message .= ' '.$validator->getTranslator()->get("(and :count more $pluralized)", compact('count'));
        }

        return $message;
    }

    public function errors(): array
    {
        return $this->validator->errors()->messages();
    }

    public function errorBag(string $errorBag): static
    {
        $this->errorBag = $errorBag;

        return $this;
    }

    public function redirectTo(string $url): static
    {
        $this->redirectTo = $url;

        return $this;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

}