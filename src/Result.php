<?php

declare(strict_types=1);

namespace PhpDb\Pgsql;

use Override;
use PgSql\Result as PgSqlResult;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Exception;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;

use function pg_affected_rows;
use function pg_fetch_assoc;
use function pg_num_fields;
use function pg_num_rows;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
class Result implements ResultInterface
{
    /** @var PgSqlResult */
    protected $resource;

    protected int $position = 0;

    protected int $count = 0;

    protected mixed $generatedValue;

    public function initialize(PgSqlResult $resource, string|int|null $generatedValue): void
    {
        $this->resource       = $resource;
        $this->count          = pg_num_rows($this->resource);
        $this->generatedValue = $generatedValue;
    }

    #[Override]
    public function current(): array|false
    {
        if ($this->count === 0) {
            return false;
        }
        return pg_fetch_assoc($this->resource, $this->position);
    }

    #[Override]
    public function next(): void
    {
        $this->position++;
    }

    #[Override]
    public function key(): int
    {
        return $this->position;
    }

    #[Override]
    public function valid(): bool
    {
        return $this->position < $this->count;
    }

    #[Override]
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[Override]
    public function buffer(): void
    {
    }

    #[Override]
    public function isBuffered(): false
    {
        return false;
    }

    #[Override]
    public function isQueryResult(): bool
    {
        return pg_num_fields($this->resource) > 0;
    }

    #[Override]
    public function getAffectedRows(): int
    {
        return pg_affected_rows($this->resource);
    }

    #[Override]
    public function getGeneratedValue(): int|string|false|null
    {
        return $this->generatedValue;
    }

    /**
     * @throws Exception\RuntimeException When this result is not a query result.
     */
    #[Override]
    public function getQueryResult(?ResultSetInterface $resultPrototype = null): ResultSetInterface
    {
        if (! $this->isQueryResult()) {
            throw new Exception\RuntimeException(
                'Cannot produce a query result set from a result that is not a query result;'
                    . ' check isQueryResult() first'
            );
        }

        $resultPrototype ??= new ResultSet();
        $resultSet         = clone $resultPrototype;
        $resultSet->initialize($this);

        return $resultSet;
    }

    /**
     * Get resource
     */
    public function getResource(): PgSqlResult
    {
        return $this->resource;
    }

    #[Override]
    public function count(): int
    {
        return $this->count;
    }

    #[Override]
    public function getFieldCount(): int
    {
        return pg_num_fields($this->resource);
    }
}
