<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Pgsql\Pdo;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDbTestAsset\Pgsql\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Adapter::class, 'getCurrentSchema')]
#[CoversMethod(AdapterInterface::class, '__construct')]
#[CoversMethod(SchemaAwareInterface::class, 'getCurrentSchema')]
#[CoversMethod(ConnectionInterface::class, 'connect')]
#[CoversMethod(ConnectionInterface::class, 'disconnect')]
#[CoversMethod(ConnectionInterface::class, 'isConnected')]
class AdapterTest extends TestCase
{
    use SetupTrait;

    public function testConnection(): void
    {
        /** @var ConnectionInterface $connection */
        $connection = $this->getAdapter(self::PDO_ADAPTER)->getDriver()->getConnection();
        $this->assertInstanceOf(ConnectionInterface::class, $connection);
    }

    public function testDriverDisconnectAfterQuoteWithPlatform(): void
    {
        $isTcpConnection = $this->isTcpConnection();

        /** @var AdapterInterface&Adapter $adapter */
        $adapter    = $this->getAdapter(self::PDO_ADAPTER);
        $connection = $adapter->getDriver()->getConnection();
        $connection->connect();
        $isConnected = $connection->isConnected();
        self::assertTrue($connection->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($connection->isConnected());
        }

        // todo: why is this not disconnecting
        $connection->disconnect();
        $isConnected = $connection->isConnected();
        self::assertFalse($connection->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($connection->isConnected());
        }

        $connection->connect();
        self::assertTrue($connection->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($connection->isConnected());
        }

        $adapter->getPlatform()->quoteValue('test');

        $connection->disconnect();

        self::assertFalse($connection->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($connection->isConnected());
        }
    }

    public function testGetCurrentSchema(): void
    {
        /** @var AdapterInterface&SchemaAwareInterface&Adapter $adapter */
        $adapter = $this->getAdapter(self::PDO_ADAPTER);
        $schema  = $adapter->getCurrentSchema();
        self::assertIsString($schema);
        self::assertNotEmpty($schema);
    }

    protected function isTcpConnection(): bool
    {
        $hostName = $this->getHostname();
        return 'localhost' !== $hostName && '127.0.0.1' !== $hostName;
    }
}
