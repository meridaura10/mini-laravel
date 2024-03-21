<?php

namespace Framework\Kernel\Http\Responses;

use ArrayObject;

class BaseJsonResponse extends Response
{
    public const DEFAULT_ENCODING_OPTIONS = 15;

    protected int $encodingOptions = self::DEFAULT_ENCODING_OPTIONS;

    protected mixed $data = null;

    public function __construct(
        mixed $data = null,
        int $status = 200,
        array $headers = [],
        bool $json = false
    ) {
        parent::__construct('', $status, $headers);

        if ($json && ! is_string($data) && ! is_numeric($data) && ! is_callable([$data, '__toString'])) {
            throw new \TypeError(sprintf('"%s": If $json is set to true, argument $data must be a string or object implementing __toString(), "%s" given.', __METHOD__, get_debug_type($data)));
        }

        $data ??= new ArrayObject();

        $json ? $this->setJson($data) : $this->setData($data);
    }

    public function setJson(string $json): static
    {
        $this->data = $json;

        return $this->update();
    }

    public function setData(mixed $data = []): static
    {
        try {
            $data = json_encode($data, $this->encodingOptions);
        } catch (\Exception $e) {
            if ($e::class === 'Exception' && str_starts_with($e->getMessage(), 'Failed calling ')) {
                throw $e->getPrevious() ?: $e;
            }
            throw $e;
        }

        return $this->setJson($data);
    }

    public function update(): static
    {
        if (! $this->headers->has('Content-Type') || $this->headers->get('Content-Type') === 'text/javascript') {
            $this->headers->set('Content-Type', 'application/json');
        }

        return $this->setContent($this->data);
    }
}
