<?php

declare(strict_types=1);

namespace PhpDb\Pgsql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\Statement as PdoStatement;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\ConfigProvider as PhpDbConfigProvider;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Pgsql\Pdo\Connection as PdoConnection;
use PhpDb\Pgsql\Pdo\Driver as PdoDriver;

/**
 * @internal
 */
final readonly class ConfigProvider
{
    public function getConfig(): array
    {
        return [
            // Standard Single Adapter configuration
            'driver'     => Driver::class,
            'connection' => [
                'host'     => 'localhost',
                'port'     => 5432,
                'username' => 'your_username',
                'password' => 'your_password',
                'database' => 'your_database',
            ],
            // Named Adapter configurations
            PhpDbConfigProvider::NAMED_ADAPTER_KEY => [
                AdapterInterface::class => [
                    'driver'     => Driver::class,
                    'connection' => [
                        'host'     => 'localhost',
                        'port'     => 5432,
                        'username' => 'your_username',
                        'password' => 'your_password',
                        'database' => 'your_database',
                    ],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                DriverInterface::class        => Driver::class,
                'pgsql'                       => Driver::class,
                'PgSQL'                       => Driver::class,
                'Postgresql'                  => Driver::class,
                'PostgreSQL'                  => Driver::class,
                PdoDriverInterface::class     => PdoDriver::class,
                'pdo_pgsql'                   => PdoDriver::class,
                'PDO_pgsql'                   => PdoDriver::class,
                'PDO_PgSQL'                   => PdoDriver::class,
                'Pdo_PgSQL'                   => PdoDriver::class,
                'PDO_postgresql'              => PdoDriver::class,
                'pdo_postgresql'              => PdoDriver::class,
                'PDO_Postgresql'              => PdoDriver::class,
                'Pdo_Postgresql'              => PdoDriver::class,
                'PDO_PostgreSQL'              => PdoDriver::class,
                ConnectionInterface::class    => Connection::class,
                MetadataInterface::class      => Metadata\Source::class,
                PdoConnectionInterface::class => PdoConnection::class,
                PlatformInterface::class      => AdapterPlatform::class,
            ],
            'factories' => [
                AdapterPlatform::class => Container\PlatformInterfaceFactory::class,
                Connection::class      => Container\ConnectionInterfaceFactory::class,
                Driver::class          => Container\DriverInterfaceFactory::class,
                Metadata\Source::class => Container\MetadataInterfaceFactory::class,
                PdoConnection::class   => Container\PdoConnectionInterfaceFactory::class,
                PdoDriver::class       => Container\PdoDriverInterfaceFactory::class,
                PdoStatement::class    => Container\PdoStatementFactory::class,
                Statement::class       => Container\StatementInterfaceFactory::class,
                // Provide the following if you wish to override the Profiler implementation
                //ProfilerInterface::class => YourCustomProfilerFactory::class,
                // Provide the following if you wish to override the ResultSet implementation
                //ResultSetInterface::class => YourCustomResultSetFactory::class,
            ],
        ];
    }

    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            //AdapterInterface::class => $this->getConfig(),
        ];
    }
}
