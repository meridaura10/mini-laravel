<?php

namespace Framework\Kernel\View\Engines;

use Framework\Kernel\Filesystem\Contracts\FilesystemInterface;
use Framework\Kernel\View\Contracts\CompilerInterface;
use Framework\Kernel\View\Exceptions\ViewException;

class CompilerEngine extends PhpEngine
{
    protected array $lastCompiled = [];

    protected array $compiledOrNotExpired = [];

    public function __construct(
        protected CompilerInterface $compiler,
        FilesystemInterface $files,
    )
    {
        parent::__construct($files);
    }

    public function get(string $path, array $data = []): string
    {
        $this->lastCompiled[] = $path;

        if (! isset($this->compiledOrNotExpired[$path]) && $this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        try {
            $results = $this->evaluatePath($this->compiler->getCompiledPath($path),$data);


        }catch (ViewException $e){
            if (! str($e->getMessage())->contains(['No such file or directory', 'File does not exist at path'])) {
                throw $e;
            }

            if (! isset($this->compiledOrNotExpired[$path])) {
                throw $e;
            }

            $this->compiler->compile($path);

            $results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
        }

        $this->compiledOrNotExpired[$path] = true;

        array_pop($this->lastCompiled);

        return $results;
    }
}