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
    public function testGetQueryResultClonesTheGivenPrototype(): void
    {
        $prototype = new ResultSet();

        $result    = new ResultStub(true, 1);
        $resultSet = $result->getQueryResult($prototype);

        self::assertNotSame($prototype, $resultSet);
    }

    public function testGetQueryResultRejectsAResultThatIsNotAQueryResult(): void
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot produce a query result set from a result that is not a query result;'
                . ' check isQueryResult() first',
        );

        $result = new ResultStub(false);
        $result->getQueryResult();
    }

    public function testGetQueryResultSeedsTheResultSetFromTheResult(): void
    {
        $result    = new ResultStub(true, 3);
        $resultSet = $result->getQueryResult();

        self::assertSame(3, $resultSet->getFieldCount());
    }
}
