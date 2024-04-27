<?php

namespace Framework\Kernel\Support;

use Framework\Kernel\Validator\Bags\MessageBag;

interface MessageProviderInterface
{
    public function getMessageBag(): MessageBag;
}