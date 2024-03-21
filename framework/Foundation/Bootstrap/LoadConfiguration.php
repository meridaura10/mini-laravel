<?php

namespace Framework\Kernel\Foundation\Bootstrap;

use Exception;
use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Config\ConfigManager;
use Framework\Kernel\Config\Contracts\ConfigManagerInterface;
use Framework\Kernel\Finder\Finder;
use Framework\Kernel\Foundation\Bootstrap\Contracts\FoundationBootstrapInterface;

class LoadConfiguration implements FoundationBootstrapInterface
{
    public function bootstrap(ApplicationInterface $app): void
    {
        $app->instance('config', $config = new ConfigManager([]));

        $this->loadConfigurationFiles($app, $config);

    }

    protected function loadConfigurationFiles(ApplicationInterface $app, ConfigManagerInterface $config): void
    {
        $files = $this->getConfigurationFiles($app);

        if (! isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }

        foreach ($files as $key => $path) {
            $config->set($key, require $path);
        }
    }

    protected function getConfigurationFiles(ApplicationInterface $app): array
    {
        $files = [];

        $configPath = realpath($app->configPath());

        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $directory = $this->getNestedDirectory($file, $configPath);

            $files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    protected function getNestedDirectory(\SplFileInfo $file, string $configPath): string
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }
}
