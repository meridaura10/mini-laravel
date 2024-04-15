<?php

namespace Framework\Kernel\Database\Exceptions;

use Framework\Kernel\Database\Eloquent\Model;
use Framework\Kernel\Support\Arr;

class ModelNotFoundException extends \RuntimeException
{
    protected string $model;

    protected array $ids;

    public function setModel(string $model,array|int|string $ids = []): static
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);

        $this->message = "No query results for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ' '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }
}