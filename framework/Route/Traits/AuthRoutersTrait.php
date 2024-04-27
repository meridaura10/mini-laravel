<?php

namespace Framework\Kernel\Route\Traits;

use Framework\Kernel\Route\Route;

trait AuthRoutersTrait
{
    public function auth(array $options = []): void
    {
        $namespace = $options['namespace'] ?: 'App\Http\Controllers';

        $this->group(['namespace' => $namespace], function () use ($options) {
            if ($options['login'] ?? true) {
                $this->get('login', ['Auth\LoginController', 'show'])->name('login');
                $this->post('login', ['Auth\LoginController', 'login']);
            }

            if ($options['logout'] ?? true) {
                $this->post('logout', 'Auth\LogoutController')->name('logout');
            }
        });
    }
}