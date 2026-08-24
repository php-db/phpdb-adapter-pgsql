<?php

declare(strict_types=1);

namespace PhpDb\Pgsql\Container;

use PhpDb\Adapter\Driver\Pdo\Statement as PdoStatement;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use Psr\Container\ContainerInterface;

final class PdoStatementFactory
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): StatementInterface&PdoStatement {
        $statementOptions = $options['options'] ?? [];
        return new PdoStatement(
            parameterContainer: new ParameterContainer(),
            options: $statementOptions,
        );
    }
}
