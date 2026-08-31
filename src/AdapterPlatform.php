<?php

declare(strict_types=1);

namespace PhpDb\Pgsql;

use Override;
use PDO;
use PgSql\Connection as PgSqlConnection;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\AbstractPlatform;
use PhpDb\Sql\Platform\Platform as SqlPlatformDecorator;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function implode;
use function is_resource;
use function pg_escape_string;
use function str_replace;

class AdapterPlatform extends AbstractPlatform
{
    final public const PLATFORM_NAME = 'PostgreSQL';

    /**
     * Overrides value from AbstractPlatform to use proper escaping for Postgres
     */
    protected string $quoteIdentifierTo = '""';

    public function __construct(
        private readonly DriverInterface|PdoDriverInterface|PDO $driver,
    ) {}

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getName(): string
    {
        return self::PLATFORM_NAME;
    }

    #[Override]
    public function getSqlPlatformDecorator(): PlatformDecoratorInterface
    {
        return new SqlPlatformDecorator($this);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifierChain($identifierChain): string
    {
        return '"' . implode('"."', (array) str_replace('"', '""', $identifierChain)) . '"';
    }

    /**
     * {@inheritDoc}
     *
     * @param scalar $value
     */
    #[Override]
    public function quoteTrustedValue($value): string
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        if (null === $quotedViaDriverValue) {
            return 'E' . parent::quoteTrustedValue($value);
        }

        return $quotedViaDriverValue;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue($value): string
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        return $quotedViaDriverValue ?? 'E' . parent::quoteValue($value);
    }

    /**
     * @param string $value
     */
    protected function quoteViaDriver($value): ?string
    {
        /** @var PgSqlConnection|string $resource */
        $resource = $this->driver instanceof DriverInterface
            ? $this->driver->getConnection()->getResource()
            : $this->driver;

        if ($resource instanceof PgSqlConnection || is_resource($resource)) {
            return '\'' . pg_escape_string($resource, $value) . '\'';
        }

        if ($resource instanceof PDO) {
            return $resource->quote($value);
        }

        return null;
    }
}
