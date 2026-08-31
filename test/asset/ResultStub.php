<?php

declare(strict_types=1);

namespace PhpDbTestAsset\Pgsql;

use Override;
use PhpDb\Pgsql\Result;

/**
 * Result::getQueryResult() only needs isQueryResult() and getFieldCount() to be
 * answerable, both of which read the pgsql resource. Overriding them keeps the
 * method unit-testable without a live connection.
 */
final class ResultStub extends Result
{
    public function __construct(private bool $isQueryResult, private int $fieldCount = 0)
    {
    }

    #[Override]
    public function isQueryResult(): bool
    {
        return $this->isQueryResult;
    }

    #[Override]
    public function getFieldCount(): int
    {
        return $this->fieldCount;
    }
}
