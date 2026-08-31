<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Pgsql\Pdo;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDbTestAsset\Pgsql\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Adapter::class, 'getCurrentSchema')]
#[CoversMethod(Adapter::class, '__construct')]
#[CoversMethod(SchemaAwareInterface::class, 'getCurrentSchema')]
#[CoversMethod(ConnectionInterface::class, 'connect')]
#[CoversMethod(ConnectionInterface::class, 'disconnect')]
#[CoversMethod(ConnectionInterface::class, 'isConnected')]
class AdapterTest extends TestCase
{
    use SetupTrait;

    #[Test]
    public function connection(): void
    {
        /** @var ConnectionInterface $connection */
        $connection = $this->getAdapter(self::PDO_ADAPTER)->getDriver()->getConnection();
        static::assertInstanceOf(ConnectionInterface::class, $connection);
    }

    #[Test]
    public function driverDisconnectAfterQuoteWithPlatform(): void
    {
        $isTcpConnection = $this->isTcpConnection();

        /** @var AdapterInterface&Adapter $adapter */
        $adapter    = $this->getAdapter(self::PDO_ADAPTER);
        $connection = $adapter->getDriver()->getConnection();
        $connection->connect();
        $isConnected = $connection->isConnected();
        static::assertTrue($connection->isConnected());
        if ($isTcpConnection) {
            static::assertTrue($connection->isConnected());
        }

        // todo: why is this not disconnecting
        $connection->disconnect();
        $isConnected = $connection->isConnected();
        static::assertFalse($connection->isConnected());
        if ($isTcpConnection) {
            static::assertFalse($connection->isConnected());
        }

        $connection->connect();
        static::assertTrue($connection->isConnected());
        if ($isTcpConnection) {
            static::assertTrue($connection->isConnected());
        }

        $adapter->getPlatform()->quoteValue('test');

        $connection->disconnect();

        static::assertFalse($connection->isConnected());
        if ($isTcpConnection) {
            static::assertFalse($connection->isConnected());
        }
    }

    #[Test]
    public function getCurrentSchema(): void
    {
        /** @var AdapterInterface&SchemaAwareInterface&Adapter $adapter */
        $adapter = $this->getAdapter(self::PDO_ADAPTER);
        $schema  = $adapter->getCurrentSchema();
        static::assertIsString($schema);
        static::assertNotEmpty($schema);
    }

    protected function isTcpConnection(): bool
    {
        $hostName = $this->getHostname();
        return 'localhost' !== $hostName && '127.0.0.1' !== $hostName;
    }
}
