<?php

declare(strict_types=1);

namespace PhpDb\Pgsql\Container;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Pgsql;
use Psr\Container\ContainerInterface;

final class PlatformInterfaceFactory
{
    /**
     * @throws ContainerException
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): PlatformInterface&Pgsql\AdapterPlatform {
        $driver = $options['driver'] ?? null;
        if (
            ! $driver instanceof Pgsql\Driver
                && ! $driver instanceof Pgsql\Pdo\Driver
        ) {
            // todo: Once latest PR is merged for 0.5.0 update to use PhpDB\Exception\ContainerException
            throw ContainerException::forService(
                PlatformInterface::class,
                self::class,
                'Invalid or missing driver provided',
            );
        }
        return new Pgsql\AdapterPlatform($driver);
    }
}
