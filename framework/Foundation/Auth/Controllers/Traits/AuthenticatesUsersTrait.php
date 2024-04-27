<?php

namespace Framework\Kernel\Foundation\Auth\Controllers\Traits;

use App\Http\Requests\Web\Auth\LoginRequest;
use Framework\Kernel\Auth\Contracts\AuthStatefulGuardInterface;
use Framework\Kernel\Facades\Services\Auth;
use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Requests\Request;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\JsonResponse;
use Framework\Kernel\Http\Responses\RedirectResponse;
use Framework\Kernel\Validator\Exceptions\ValidationException;
use Framework\Kernel\View\Contracts\ViewInterface;

trait AuthenticatesUsersTrait
{
    use RedirectsUsersTrait;
    public function show(): ViewInterface
    {
        return view('pages.auth.login');
    }

    public function login(LoginRequest $request): mixed
    {
        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    protected function sendLoginResponse(RequestInterface $request): JsonResponse|RedirectResponse
    {
        $request->session()->regenerate();

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect()->intended($this->redirectPath());
    }

    protected function sendFailedLoginResponse(Request $request): ResponseInterface
    {
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    protected function attemptLogin(RequestInterface $request): bool
    {
        return $this->guard()->attempt(
            $this->credentials($request), $request->boolean('remember')
        );
    }

    protected function credentials(RequestInterface $request): array
    {
        return $request->only('email', 'password');
    }

    protected function guard(): AuthStatefulGuardInterface
    {
        return Auth::guard();
    }
}