<?php

declare(strict_types=1);

namespace PhpDb\Pgsql\Pdo;

use Override;
use PDO;
use PDOException;
use PDOStatement;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Exception;

use function array_diff_key;
use function implode;
use function is_array;
use function is_int;
use function str_contains;
use function strtolower;

class Connection extends AbstractPdoConnection
{
    /**
     * Constructor
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        PDO|array $connectionParameters,
    ) {
        if (is_array($connectionParameters)) {
            $this->setConnectionParameters($connectionParameters);
        } elseif ($connectionParameters instanceof PDO) {
            $this->setResource($connectionParameters);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\InvalidConnectionParametersException
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function connect(): ConnectionInterface&PdoConnectionInterface
    {
        if ($this->resource) {
            return $this;
        }

        $dsn     = $username = $password = $hostname = $database = null;
        $options = [];
        foreach ($this->connectionParameters as $key => $value) {
            $result = match (strtolower($key)) {
                'dsn'                                => $dsn = (string) $value,
                'user', 'username'                   => $username = (string) $value,
                'password', 'passwd', 'pw'           => $password = (string) $value,
                'host', 'hostname'                   => $hostname = (string) $value,
                'port'                               => $port = (int) $value,
                'dbname', 'database', 'db', 'schema' => $database = (string) $value,
                'unix_socket'                        => $unixSocket = (string) $value,
                // todo: should we suppport sslmode for pdo pgsql?
                'driver_options' => (static function (&$options, $value): void {
                    $value   = (array) $value;
                    $options = array_diff_key($options, $value) + $value;
                })($options, $value),
                default          => $options[$key] = $value,
            };
        }
        unset($result);

        if (! isset($dsn)) {
            $dsn = [];
            if (isset($unixSocket)) {
                // If a unix socket is provided, use it as the hostname
                $hostname = $unixSocket;
            }
            if (isset($hostname)) {
                $dsn[] = "host={$hostname}";
            }
            if (isset($port)) {
                $dsn[] = "port={$port}";
            }
            if (isset($database)) {
                $dsn[] = "dbname={$database}";
            }
            // todo: if sslmode is supported then $username and $password should not be passed in the dsn
            if (isset($username)) {
                $dsn[] = "user={$username}";
            }
            if (isset($password)) {
                $dsn[] = "password={$password}";
            }

            $dsn = 'pgsql:' . implode(';', $dsn);
        }

        if (
            ! str_contains($dsn, 'host=')
                && ! str_contains($dsn, 'dbname=')
                && ! str_contains($dsn, 'user=')
        ) {
            throw new Exception\InvalidConnectionParametersException(
                'A dsn was not provided or could not be constructed from your parameters',
                $this->connectionParameters,
            );
        }

        $this->dsn = $dsn;

        try {
            $this->resource = new PDO($dsn, $username, $password, $options);
            $this->resource->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->driverName = strtolower($this->resource->getAttribute(PDO::ATTR_DRIVER_NAME));
        } catch (PDOException $e) {
            $code = $e->getCode();
            if (! is_int($code)) {
                $code = 0;
            }
            throw new Exception\RuntimeException("Connect Error: {$e->getMessage()}", $code, $e);
        }

        return $this;
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

        /** @var PDOStatement $result */
        $result = $this->resource->query('SELECT CURRENT_SCHEMA');
        if ($result instanceof PDOStatement) {
            return $result->fetchColumn();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @param ?string $name
     */
    #[Override]
    public function getLastGeneratedValue($name = null): string|int|false
    {
        try {
            return $this->resource->lastInsertId($name);
        } catch (\Exception) {
        }

        return false;
    }
}
