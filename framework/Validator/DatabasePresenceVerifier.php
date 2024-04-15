<?php

namespace Framework\Kernel\Validator;

use Closure;
use Framework\Kernel\Database\Contracts\BuilderInterface;
use Framework\Kernel\Database\Contracts\QueryBuilderInterface;
use Framework\Kernel\Database\DatabaseManager;
use Framework\Kernel\Validator\Contracts\DatabasePresenceVerifierInterface;

class DatabasePresenceVerifier implements DatabasePresenceVerifierInterface
{
    protected ?string $connection = null;

    public function __construct(
        protected DatabaseManager $db,
    )
    {

    }

    public function setConnection(?string $connection = null): void
    {
        $this->connection = $connection;
    }

    protected function table(string $table): QueryBuilderInterface
    {
        return $this->db->connection($this->connection)->table($table)->useWritePdo();
    }

    public function getCount(string $collection, string $column, string $value, ?string $excludeId = null, ?string $idColumn = null, array $extra = []): int
    {
        $query = $this->table($collection)->where($column, '=', $value);

        if (!is_null($excludeId) && $excludeId !== 'NULL') {
            $query->where($idColumn ?: 'id', '<>', $excludeId);
        }

        return $this->addConditions($query, $extra)->count();
    }

    public function getMultiCount(string $collection, string $column, array $values, array $extra = []): int
    {
        // TODO: Implement getMultiCount() method.
    }

    protected function addConditions(QueryBuilderInterface $query,array $conditions): QueryBuilderInterface
    {
        foreach ($conditions as $key => $value) {
            if ($value instanceof Closure) {
                $query->where(function ($query) use ($value) {
                    $value($query);
                });
            } else {
                $this->addWhere($query, $key, $value);
            }
        }

        return $query;
    }

    protected function addWhere(QueryBuilderInterface $query,string $key,string $extraValue): void
    {
        if ($extraValue === 'NULL') {
            $query->whereNull($key);
        } elseif ($extraValue === 'NOT_NULL') {
            $query->whereNotNull($key);
        } elseif (str_starts_with($extraValue, '!')) {
            $query->where($key, '!=', mb_substr($extraValue, 1));
        } else {
            $query->where($key, $extraValue);
        }
    }
}