<?php

namespace Framework\Kernel\Database\Query\Processors;

use Framework\Kernel\Database\Contracts\QueryBuilderInterface;

class Processor
{
    public function processSelect(QueryBuilderInterface $query, $results)
    {
        return $results;
    }

    public function processInsertGetId(QueryBuilderInterface $query, string $sql, array $values, ?string $sequence = null): int
    {
        $query->getConnection()->insert($sql, $values);

        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);

        return (int) $id;
    }

    public function processTables(array $results): array
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->name,
                'schema' => $result->schema ?? null,
                'size' => isset($result->size) ? (int) $result->size : null,
                'comment' => $result->comment ?? null,
                'collation' => $result->collation ?? null,
                'engine' => $result->engine ?? null,
            ];
        }, $results);
    }
}
