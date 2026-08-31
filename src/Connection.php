<?php

declare(strict_types=1);

namespace PhpDb\Pgsql;

use Override;
use PgSql\Connection as PgSqlConnection;
use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverAwareInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Exception;

use function array_filter;
use function http_build_query;
use function is_array;
use function pg_close;
use function pg_connect;
use function pg_fetch_result;
use function pg_last_error;
use function pg_query;
use function pg_set_client_encoding;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_replace;
use function strtolower;
use function urldecode;

use const PGSQL_CONNECT_FORCE_NEW;

class Connection extends AbstractConnection implements DriverAwareInterface
{
    protected DriverInterface|Driver $driver;

    /** @var ?PgSqlConnection */
    protected $resource;

    /** PostgreSQL connection type */
    protected ?int $type = null;

    public function __construct(PgSqlConnection|array $connectionInfo)
    {
        if (is_array($connectionInfo)) {
            $this->setConnectionParameters($connectionInfo);
        } else {
            $this->setResource($connectionInfo);
        }
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function beginTransaction(): static
    {
        if ($this->inTransaction()) {
            throw new Exception\RuntimeException('Nested transactions are not supported');
        }

        if (! $this->isConnected()) {
            $this->connect();
        }

        pg_query($this->resource, 'BEGIN');
        $this->inTransaction = true;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function commit(): static
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        if (! $this->inTransaction()) {
            return $this; // We ignore attempts to commit non-existing transaction
        }

        pg_query($this->resource, 'COMMIT');
        $this->inTransaction = false;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException On failure.
     */
    #[Override]
    public function connect(): static
    {
        if ($this->resource instanceof PgSqlConnection) {
            return $this;
        }

        $connection = $this->getConnectionString();
        set_error_handler(static function ($number, $string) {
            throw new Exception\RuntimeException(
                self::class . '::connect: Unable to connect to database',
                $number,
                new Exception\ErrorException($string, $number),
            );
        });
        try {
            $this->resource = pg_connect($connection);
        } finally {
            restore_error_handler();
        }

        if (false === $this->resource) {
            throw new Exception\RuntimeException(sprintf(
                '%s: Unable to connect to database',
                __METHOD__,
            ));
        }

        if (! empty($this->connectionParameters['charset'])) {
            if (pg_set_client_encoding($this->resource, $this->connectionParameters['charset']) === -1) {
                throw new Exception\RuntimeException(sprintf(
                    "%s: Unable to set client encoding '%s'",
                    __METHOD__,
                    $this->connectionParameters['charset'],
                ));
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function disconnect(): static
    {
        pg_close($this->resource);
        $this->resource = null;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\InvalidQueryException
     */
    #[Override]
    public function execute($sql): ResultInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $this->profiler?->profilerStart($sql);

        $resultResource = pg_query($this->resource, $sql);

        $this->profiler?->profilerFinish();

        // if the returnValue is something other than a pg result resource, bypass wrapping it
        if (false === $resultResource) {
            throw new Exception\InvalidQueryException(pg_last_error($this->resource));
        }

        /** @phpstan-ignore argument.type */
        return $this->driver->createResult($resultResource);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getCurrentSchema(): string|false
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $result = pg_query($this->resource, 'SELECT CURRENT_SCHEMA AS "currentschema"');
        if (false === $result) {
            return false;
        }

        return pg_fetch_result($result, 0, 'currentschema');
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        if (null === $name) {
            return null;
        }
        $result = pg_query(
            $this->resource,
            'SELECT CURRVAL(\'' . str_replace('\'', '\\\'', $name) . '\') as "currval"',
        );

        return pg_fetch_result($result, 0, 'currval');
    }

    /** @phpstan-ignore method.childReturnType */
    #[Override]
    public function getResource(): ?PgSqlConnection
    {
        return $this->resource;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function isConnected(): bool
    {
        return $this->resource instanceof PgSqlConnection;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function rollback(): static
    {
        if (! $this->isConnected()) {
            throw new Exception\RuntimeException('Must be connected before you can rollback');
        }

        if (! $this->inTransaction()) {
            throw new Exception\RuntimeException('Must call beginTransaction() before you can rollback');
        }

        pg_query($this->resource, 'ROLLBACK');
        $this->inTransaction = false;

        return $this;
    }

    #[Override]
    public function setDriver(
        DriverInterface|Driver $driver,
    ): ConnectionInterface&DriverAwareInterface {
        $this->driver = $driver;

        return $this;
    }

    public function setResource(
        PgSqlConnection $resource,
    ): ConnectionInterface&DriverAwareInterface {
        $this->resource = $resource;

        return $this;
    }

    public function setType(?int $type): ConnectionInterface&DriverAwareInterface
    {
        $invalidConectionType = PGSQL_CONNECT_FORCE_NEW !== $type;
        if ($invalidConectionType) {
            throw new Exception\InvalidArgumentException(
                'Connection type is not valid. (See: https://php.net/manual/en/function.pg-connect.php)',
            );
        }
        $this->type = $type;

        return $this;
    }

    /**
     * Get Connection String
     */
    private function getConnectionString(): string
    {
        $parameters = $this->connectionParameters;
        $conn       = [];
        foreach ($parameters as $name => $value) {
            $name = match (strtolower($name)) {
                'host', 'hostname'                   => 'host',
                'user', 'username'                   => 'user',
                'password', 'passwd', 'pw'           => 'password',
                'database', 'dbname', 'db', 'schema' => 'dbname',
                'port'                               => 'port',
                'socket'                             => 'socket',
                default                              => throw new Exception\InvalidArgumentException(
                    'Connection parameter "' . $name . '" is not valid for Pgsql adapter',
                ),
            };
            if ('port' === $name && null !== $value) {
                $value = (int) $value;
            }
            $conn[$name] = $value;
        }
        // todo:
        return urldecode(
            http_build_query(
                array_filter($conn),
                '',
                ' ',
            ),
        );
    }
}
