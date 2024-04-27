<?php

namespace Framework\Kernel\Session;

use Framework\Kernel\Session\Contracts\SessionStoreInterface;
use Framework\Kernel\Support\Manager;
use SessionHandlerInterface;

    class SessionManager extends Manager
{
    protected function createFileDriver(): SessionStoreInterface
    {
        return $this->createNativeDriver();
    }

    protected function createNativeDriver(): SessionStoreInterface
    {
        $lifetime = $this->config->get('session.lifetime');

        return $this->buildSession(new FileSessionHandler(
            $this->app->make('files'), $this->config->get('session.files'), $lifetime
        ));
    }

    protected function buildSession(SessionHandlerInterface $handler): SessionStoreInterface
    {
        return $this->config->get('session.encrypt')
            ? $this->buildEncryptedSession($handler)
            : new SessionStore(
                $this->config->get('session.cookie'),
                $handler,
                $id = null,
                $this->config->get('session.serialization', 'php')
            );
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('session.driver');
    }

    public function getSessionConfig(): array
    {
        return $this->config->get('session');
    }
}