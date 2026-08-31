<?php

declare(strict_types=1);

namespace PhpDb\Pgsql\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Pgsql\Pdo;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
final class PdoDriverInterfaceFactory
{
    public function __invoke(
        ContainerInterface&ServiceManager $container,
        string $requestedName,
        ?array $options = null,
    ): PdoDriverInterface&Pdo\Driver {
        if (! $container->has('config')) {
            // todo: Once latest PR is merged for 0.5.0 update to use PhpDB\Exception\ContainerException
            throw ContainerException::forService(
                Pdo\Driver::class,
                self::class,
                'Container is missing a config service',
            );
        }
        $config               = $container->get('config');
        $connectionParameters = $options['connection']
            ?? $config[$requestedName]['connection']
            ?? $config[AdapterInterface::class]['adapters'][$requestedName]['connection']
                ?? null;
        $connection = $container->build(Pdo\Connection::class, ['connection' => $connectionParameters]);
        return new Pdo\Driver(
            connection: $connection,
            statementPrototype: $container->build(Statement::class, ['options' => $options['options'] ?? []]),
            resultPrototype: new Result(),
        );
    }
}
