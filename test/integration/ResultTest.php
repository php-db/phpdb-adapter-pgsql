<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Pgsql;

use PhpDb\Adapter\Exception;
use PhpDb\Pgsql\Result;
use PhpDbTestAsset\Pgsql\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Result::class, 'getQueryResult')]
#[CoversMethod(Result::class, 'isQueryResult')]
class ResultTest extends TestCase
{
    use SetupTrait;

    public function testGetQueryResultSeedsTheResultSetFromASelect(): void
    {
        $result = $this->getAdapter()->executeQuery('SELECT id, name, value FROM test');

        self::assertSame($result->getFieldCount(), $result->getQueryResult()->getFieldCount());
    }

    public function testGetQueryResultIteratesTheSelectedRows(): void
    {
        $result = $this->getAdapter()->executeQuery('SELECT name FROM test ORDER BY id');

        $names = [];
        foreach ($result->getQueryResult() as $row) {
            $names[] = $row['name'];
        }

        self::assertSame(['foo', 'bar'], $names);
    }

    public function testAStatementReturningNoFieldsIsNotAQueryResult(): void
    {
        $result = $this->getAdapter()->executeQuery('SET search_path TO public');

        self::assertFalse($result->isQueryResult());
    }

    public function testGetQueryResultRejectsAStatementThatReturnsNoFields(): void
    {
        $result = $this->getAdapter()->executeQuery('SET search_path TO public');

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot produce a query result set from a result that is not a query result;'
                . ' check isQueryResult() first'
        );

        $result->getQueryResult();
    }
}
