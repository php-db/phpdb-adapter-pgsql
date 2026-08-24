<?php

declare(strict_types=1);

namespace PhpDbTest\Pgsql;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\Statement as PdoStatement;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Pgsql;
use PhpDb\Pgsql\Pdo\Connection as PdoConnection;
use PhpDb\Pgsql\Pdo\Driver as PdoDriver;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Pgsql\ConfigProvider::class, '__invoke')]
#[CoversMethod(Pgsql\ConfigProvider::class, 'getDependencies')]
final class ConfigProviderTest extends TestCase
{
    /** @var array<string, array<array-key, string>> */
    private array $config = [
        'aliases'   => [
            DriverInterface::class        => Pgsql\Driver::class,
            'pgsql'                       => Pgsql\Driver::class,
            'PgSQL'                       => Pgsql\Driver::class,
            'Postgresql'                  => Pgsql\Driver::class,
            'PostgreSQL'                  => Pgsql\Driver::class,
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
            ConnectionInterface::class    => Pgsql\Connection::class,
            MetadataInterface::class      => Pgsql\Metadata\Source::class,
            PdoConnectionInterface::class => Pgsql\Pdo\Connection::class,
            PlatformInterface::class      => Pgsql\AdapterPlatform::class,
        ],
        'factories' => [
            Pgsql\AdapterPlatform::class => Pgsql\Container\PlatformInterfaceFactory::class,
            Pgsql\Connection::class      => Pgsql\Container\ConnectionInterfaceFactory::class,
            Pgsql\Driver::class          => Pgsql\Container\DriverInterfaceFactory::class,
            Pgsql\Metadata\Source::class => Pgsql\Container\MetadataInterfaceFactory::class,
            Pgsql\Statement::class       => Pgsql\Container\StatementInterfaceFactory::class,
            PdoConnection::class         => Pgsql\Container\PdoConnectionInterfaceFactory::class,
            PdoDriver::class             => Pgsql\Container\PdoDriverInterfaceFactory::class,
            PdoStatement::class          => Pgsql\Container\PdoStatementFactory::class,
        ],
    ];

    #[Depends('testProvidesExpectedConfiguration')]
    public function testInvocationProvidesDependencyConfiguration(Pgsql\ConfigProvider $provider): void
    {
        self::assertEquals(['dependencies' => $provider->getDependencies()], $provider());
    }

    public function testProvidesExpectedConfiguration(): Pgsql\ConfigProvider
    {
        $provider = new Pgsql\ConfigProvider();
        self::assertEquals($this->config, $provider->getDependencies());

        return $provider;
    }
}
