<?php

namespace Framework\Kernel\Http\Responses;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Session\Contracts\SessionStoreInterface;
use Framework\Kernel\Support\MessageProviderInterface;
use Framework\Kernel\Support\ViewErrorBag;
use Framework\Kernel\Validator\Bags\MessageBag;

class RedirectResponse extends BaseRedirectResponse
{
    protected ?SessionStoreInterface $session = null;

    protected ?RequestInterface $request = null;

    public function withInput(array $input = null): static
    {
        $this->session->flashInput($this->removeFilesFromInput(
            ! is_null($input) ? $input : $this->request->input()
        ));

        return $this;
    }

    protected function removeFilesFromInput(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->removeFilesFromInput($value);
            }

//            if ($value instanceof SymfonyUploadedFile) {
//                unset($input[$key]);
//            }
        }

        return $input;
    }

    public function withErrors(array|MessageProviderInterface|string $provider,string $key = 'default'): static
    {


        $value = $this->parseErrors($provider);

        $errors = $this->session->get('errors', new ViewErrorBag);

        if (! $errors instanceof ViewErrorBag) {
            $errors = new ViewErrorBag;
        }

        $this->session->flash(
            'errors', $errors->put($key, $value)
        );


        return $this;
    }

    protected function parseErrors(array|MessageProviderInterface|string $provider): MessageBag
    {;
        if ($provider instanceof MessageProviderInterface) {
            return $provider->getMessageBag();
        }

        return new MessageBag((array) $provider);
    }


    public function setSession(SessionStoreInterface $session): void
    {
        $this->session = $session;
    }

    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }
}