<?php

declare(strict_types=1);

namespace PhpDb\Pgsql;

use Override;
use PgSql\Result as PgSqlResult;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverAwareInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

use function array_intersect_key;
use function extension_loaded;
use function is_string;

/**
 * @final
 */
class Driver implements DriverInterface, ProfilerAwareInterface
{
    protected ?ProfilerInterface $profiler = null;

    /** @var array */
    protected $options = [
        'buffer_results' => false,
    ];

    public function __construct(
        protected readonly DriverAwareInterface&ConnectionInterface&Connection $connection,
        protected readonly DriverAwareInterface&StatementInterface&Statement $statementPrototype = new Statement(),
        protected readonly ResultInterface&Result $resultPrototype = new Result(),
        array $options = [],
    ) {
        $this->checkEnvironment();

        //todo: verify this usage
        $options = array_intersect_key([...$this->options, ...$options], $this->options);

        $this->connection->setDriver($this);

        $this->statementPrototype->setDriver($this);
    }

    #[Override]
    public function checkEnvironment(): bool
    {
        if (! extension_loaded('pgsql')) {
            throw new Exception\RuntimeException(
                'The PostgreSQL (pgsql) extension is required for this Driver but the extension is not loaded',
            );
        }
        return true;
    }

    /**
     * Create result
     *
     * @param PgSqlResult|resource $resource
     */
    #[Override]
    public function createResult($resource): ResultInterface&Result
    {
        /** @var Result $result */
        $result = clone $this->resultPrototype;
        $result->initialize($resource, $this->connection->getLastGeneratedValue());

        return $result;
    }

    /**
     * Create statement
     *
     * @param resource|string|null $sqlOrResource
     */
    #[Override]
    public function createStatement($sqlOrResource = null): StatementInterface&Statement
    {
        $statement = clone $this->statementPrototype;

        if (is_string($sqlOrResource)) {
            $statement->setSql($sqlOrResource);
        }

        if (! $this->connection->isConnected()) {
            $this->connection->connect();
        }

        $statement->initialize($this->connection->getResource());

        return $statement;
    }

    #[Override]
    public function formatParameterName(string $name, ?string $type = null): string
    {
        return '$#';
    }

    #[Override]
    public function getConnection(): ConnectionInterface&Connection
    {
        return $this->connection;
    }

    /**
     * Get last generated value
     */
    #[Override]
    public function getLastGeneratedValue(?string $name = null): int|string|false|null
    {
        return $this->connection->getLastGeneratedValue($name);
    }

    #[Override]
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    public function getResultPrototype(): ResultInterface&Result
    {
        return $this->resultPrototype;
    }

    #[Override]
    public function setProfiler(
        ProfilerInterface $profiler,
    ): DriverInterface&ProfilerAwareInterface {
        $this->profiler = $profiler;

        // @phpstan-ignore instanceof.alwaysTrue
        if ($this->connection instanceof ProfilerAwareInterface) {
            $this->connection->setProfiler($profiler);
        }

        // @phpstan-ignore instanceof.alwaysTrue
        if ($this->statementPrototype instanceof ProfilerAwareInterface) {
            $this->statementPrototype->setProfiler($profiler);
        }

        return $this;
    }
}
