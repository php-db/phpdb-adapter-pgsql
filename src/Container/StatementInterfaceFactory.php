<?php

declare(strict_types=1);

namespace PhpDb\Pgsql\Container;

use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Pgsql;
use Psr\Container\ContainerInterface;

final class StatementInterfaceFactory
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): StatementInterface {
        return new Pgsql\Statement(
            options: $options['options'] ?? false,
        );
    }
}
