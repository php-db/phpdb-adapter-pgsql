<?php

declare(strict_types=1);

namespace PhpDbTest\Pgsql;

use PhpDb\Adapter\Exception;
use PhpDb\Pgsql\Result;
use PhpDb\ResultSet\ResultSet;
use PhpDbTestAsset\Pgsql\ResultStub;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Result::class, 'getQueryResult')]
class ResultTest extends TestCase
{
    #[Test]
    public function getQueryResultClonesTheGivenPrototype(): void
    {
        $prototype = new ResultSet();

        $result    = new ResultStub(true, 1);
        $resultSet = $result->getQueryResult($prototype);

        static::assertNotSame($prototype, $resultSet);
    }

    #[Test]
    public function getQueryResultRejectsAResultThatIsNotAQueryResult(): void
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot produce a query result set from a result that is not a query result;'
                . ' check isQueryResult() first',
        );

        $result = new ResultStub(false);
        $result->getQueryResult();
    }

    #[Test]
    public function getQueryResultSeedsTheResultSetFromTheResult(): void
    {
        $result    = new ResultStub(true, 3);
        $resultSet = $result->getQueryResult();

        static::assertSame(3, $resultSet->getFieldCount());
    }
}
