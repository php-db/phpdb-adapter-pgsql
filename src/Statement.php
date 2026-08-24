<?php

declare(strict_types=1);

namespace PhpDb\Pgsql;

use Override;
use PgSql\Connection as PgSqlConnection;
use PgSql\Result as PgSqlResult;
use PhpDb\Adapter\Driver\DriverAwareInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

use function is_array;
use function pg_execute;
use function pg_last_error;
use function pg_prepare;
use function preg_replace_callback;

class Statement implements StatementInterface, DriverAwareInterface, ProfilerAwareInterface
{
    protected bool $bufferResults = false;

    protected static int $statementIndex = 0;

    protected string $statementName = 'statement';

    protected DriverInterface|Driver $driver;

    protected ?ProfilerInterface $profiler = null;

    protected PgSqlConnection $pgsql;

    protected PgSqlResult|false $resource;

    protected string $sql;

    public function __construct(
        protected ParameterContainer $parameterContainer = new ParameterContainer(),
        array|bool $options = false,
    ) {
        if (is_array($options)) {
            $this->bufferResults = $options['buffer_results'] ?? false;
        } else {
            $this->bufferResults = $options;
        }
    }

    /**
     * Execute
     *
     * @throws Exception\InvalidQueryException
     */
    #[Override]
    public function execute(ParameterContainer|array|null $parameters = null): ?ResultInterface
    {
        if (! $this->isPrepared()) {
            $this->prepare();
        }

        /** START Standard ParameterContainer Merging Block */
        if ($parameters instanceof ParameterContainer) {
            $this->parameterContainer = $parameters;
            $parameters               = null;
        }

        if (is_array($parameters)) {
            $this->parameterContainer->setFromArray($parameters);
        }

        if ($this->parameterContainer->count() > 0) {
            $parameters = $this->parameterContainer->getPositionalArray();
        }
        /** END Standard ParameterContainer Merging Block */

        $this->profiler?->profilerStart($this);

        $resultResource = pg_execute($this->pgsql, $this->statementName, (array) $parameters);

        $this->profiler?->profilerFinish();

        if (false === $resultResource) {
            throw new Exception\InvalidQueryException(pg_last_error());
        }
        /** @phpstan-ignore argument.type */
        return $this->driver->createResult($resultResource);
    }

    #[Override]
    public function getParameterContainer(): ParameterContainer
    {
        return $this->parameterContainer;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    #[Override]
    public function getResource(): PgSqlResult|false
    {
        return $this->resource;
    }

    #[Override]
    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function initialize(PgSqlConnection $pgsql): void
    {
        $this->pgsql = $pgsql;
    }

    #[Override]
    public function isPrepared(): bool
    {
        return isset($this->resource);
    }

    #[Override]
    public function prepare(
        ?string $sql = null,
    ): StatementInterface&DriverAwareInterface&ProfilerAwareInterface {
        $sql ??= $this->sql;

        $pCount = 1;
        $sql    = preg_replace_callback(
            '#\$\##',
            static function () use (&$pCount) {
                return '$' . $pCount++;
            },
            $sql,
        );

        $this->sql           = $sql;
        $this->statementName .= ++static::$statementIndex;
        $this->resource      = pg_prepare($this->pgsql, $this->statementName, $sql);
        return $this;
    }

    #[Override]
    public function setDriver(
        DriverInterface $driver,
    ): StatementInterface&DriverAwareInterface&ProfilerAwareInterface {
        $this->driver = $driver;
        return $this;
    }

    #[Override]
    public function setParameterContainer(
        ParameterContainer $parameterContainer,
    ): StatementInterface&DriverAwareInterface&ProfilerAwareInterface {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    #[Override]
    public function setProfiler(
        ProfilerInterface $profiler,
    ): StatementInterface&DriverAwareInterface&ProfilerAwareInterface {
        $this->profiler = $profiler;
        return $this;
    }

    #[Override]
    public function setSql(
        ?string $sql,
    ): StatementInterface&DriverAwareInterface&ProfilerAwareInterface {
        $this->sql = $sql;
        return $this;
    }
}
