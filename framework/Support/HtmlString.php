<?php

namespace Framework\Kernel\Support;

use Framework\Kernel\Contracts\Support\Htmlable;

class HtmlString implements Htmlable
{
    public function __construct(
        protected string $html,
    )
    {

    }

    public function toHtml(): string
    {
       return $this->html;
    }


    public function isEmpty(): bool
    {
        return $this->html === '';
    }
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }


    public function __toString(): string
    {
        return $this->toHtml();
    }
}