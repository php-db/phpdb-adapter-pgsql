<?php

declare(strict_types=1);

namespace PhpDbTest\Pgsql;

use PhpDb\Adapter\Exception;
use PhpDb\Pgsql\Result;
use PhpDb\ResultSet\ResultSet;
use PhpDbTestAsset\Pgsql\ResultStub;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Result::class, 'getQueryResult')]
class ResultTest extends TestCase
{
    public function testGetQueryResultSeedsTheResultSetFromTheResult(): void
    {
        $resultSet = (new ResultStub(true, 3))->getQueryResult();

        self::assertSame(3, $resultSet->getFieldCount());
    }

    public function testGetQueryResultClonesTheGivenPrototype(): void
    {
        $prototype = new ResultSet();

        $resultSet = (new ResultStub(true, 1))->getQueryResult($prototype);

        self::assertNotSame($prototype, $resultSet);
    }

    public function testGetQueryResultRejectsAResultThatIsNotAQueryResult(): void
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot produce a query result set from a result that is not a query result;'
                . ' check isQueryResult() first'
        );

        (new ResultStub(false))->getQueryResult();
    }
}
