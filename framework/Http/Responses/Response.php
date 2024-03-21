<?php

namespace Framework\Kernel\Http\Responses;

use ArrayObject;
use Framework\Kernel\Contracts\Support\Arrayable;
use Framework\Kernel\Contracts\Support\Jsonable;
use Framework\Kernel\Contracts\Support\Renderable;
use Framework\Kernel\Http\Responses\Headers\ResponseHeaderBag;
use Framework\Kernel\View\Exceptions\InvalidArgumentException;
use JsonSerializable;

class Response extends BaseResponse
{
    public function __construct(mixed $content = '', $status = 200, array $headers = [])
    {
        $this->headers = new ResponseHeaderBag($headers);

        $this->setContent($content);
        $this->setStatusCode($status);
        $this->setProtocolVersion('1.0');

    }

    public function setContent(mixed $content): static
    {
        if ($this->shouldBeJson($content)) {
            $this->headers->set('Content-Type', 'application/json');

            $content = $this->morphToJson($content);

            if (! $content) {
                throw new InvalidArgumentException(json_last_error_msg());
            }

        } elseif ($content instanceof Renderable) {
            $content = $content->render();
        }

        parent::setContent($content);

        return $this;
    }

    protected function shouldBeJson($content): bool
    {
        return $content instanceof Arrayable ||
            $content instanceof Jsonable ||
            $content instanceof ArrayObject ||
            $content instanceof JsonSerializable ||
            is_array($content);
    }

    protected function morphToJson(Arrayable|Jsonable|ArrayObject|JsonSerializable|array $content): ?string
    {
        if ($content instanceof Jsonable) {
            return $content->toJson();
        } elseif ($content instanceof Arrayable) {
            return json_encode($content->toArray());
        }

        return json_encode($content);
    }
}
