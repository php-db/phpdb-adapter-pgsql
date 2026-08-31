<?php

declare(strict_types=1);

namespace PhpDb\Pgsql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Exception\ContainerException;
use PhpDb\Pgsql\Pdo;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * @internal
 */
final class PdoConnectionInterfaceFactory
{
    /**
     * @throws ContainerException
     * @throws InvalidConnectionParametersException
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): PdoConnectionInterface&Pdo\Connection {
        if (! $container->has('config')) {
            // todo: Once latest PR is merged for 0.5.0 update to use PhpDB\Exception\ContainerException
            throw ContainerException::forService(
                Pdo\Connection::class,
                self::class,
                'Container is missing a config service',
            );
        }
        $config         = $container->get('config');
        $connectionInfo = $options['connection']
            ?? $config[$requestedName]['connection']
            ?? $config[AdapterInterface::class]['adapters'][$requestedName]['connection']
                ?? null;
        if (null === $connectionInfo || ! is_array($connectionInfo) || [] === $connectionInfo) {
            throw new InvalidConnectionParametersException(
                'Connection configuration must be an array of parameters',
                $connectionInfo,
            );
        }
        return new Pdo\Connection($connectionInfo);
    }
}
