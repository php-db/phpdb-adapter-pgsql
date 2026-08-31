<?php

declare(strict_types=1);

namespace PhpDb\Pgsql\Container;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Adapter\Pgsql\Exception\ContainerException;
use PhpDb\Pgsql\Connection;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * This factory can only be used via the ServiceManager's build() method
 *
 * @internal
 */
final class ConnectionInterfaceFactory
{
    /**
     * @throws ContainerException
     * @throws InvalidConnectionParametersException
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): ConnectionInterface&Connection {
        if (! is_array($options['connection']) || [] === $options['connection']) {
            throw new InvalidConnectionParametersException(
                'Connection configuration must be an array of parameters passed via $options["connection"]',
                $options['connection'],
            );
        }
        return new Connection($options['connection']);
    }
}
