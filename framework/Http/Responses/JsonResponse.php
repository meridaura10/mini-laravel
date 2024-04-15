<?php

namespace Framework\Kernel\Http\Responses;

class JsonResponse extends BaseJsonResponse
{
    public function __construct(mixed $data = null,int $status = 200,array $headers = [],int $options = 0,bool|string $json = false)
    {
        $this->encodingOptions = $options;

        parent::__construct($data, $status, $headers, $json);
    }
}
