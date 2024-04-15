<?php

namespace Framework\Kernel\Http\Requests\Traits;

use Framework\Kernel\Support\Str;

trait InteractsWithContentTypesTrait
{
    public function isJson(): bool
    {
        return Str::contains($this->header('CONTENT_TYPE') ?? '', ['/json', '+json']);
    }

    public function expectsJson(): bool
    {
        return ($this->ajax() && ! $this->pjax() && $this->acceptsAnyContentType()) || $this->wantsJson();
    }

    public function wantsJson(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();

        return isset($acceptable[0]) && Str::contains(strtolower($acceptable[0]), ['/json', '+json']);
    }

    public function acceptsAnyContentType(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();

        return count($acceptable) === 0 || (
                isset($acceptable[0]) && ($acceptable[0] === '*/*' || $acceptable[0] === '*')
            );
    }
}