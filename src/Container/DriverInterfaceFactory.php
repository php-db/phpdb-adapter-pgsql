<?php

declare(strict_types=1);

namespace PhpDb\Pgsql\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Pgsql;
use Psr\Container\ContainerInterface;

final class DriverInterfaceFactory
{
    public function __invoke(
        ContainerInterface&ServiceManager $container,
        string $requestedName,
        ?array $options = null,
    ): DriverInterface {
        if (! $options['connection']) {
            throw ContainerException::forService(
                Pgsql\Driver::class,
                self::class,
                '$options["connection"] must contain an array of connection configuration.',
            );
        }

        $connection = $container->build(Pgsql\Connection::class, $options);
        return new Pgsql\Driver(
            connection: $connection,
            statementPrototype: $container->build(Pgsql\Statement::class, ['options' => $options['options'] ?? []]),
            resultPrototype: new Pgsql\Result(),
        );
    }
}
