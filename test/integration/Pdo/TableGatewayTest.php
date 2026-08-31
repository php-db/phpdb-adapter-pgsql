<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Pgsql\Pdo;

use PhpDb\Sql\TableIdentifier;
use PhpDb\TableGateway\Feature\FeatureSet;
use PhpDb\TableGateway\Feature\SequenceFeature;
use PhpDb\TableGateway\TableGateway;
use PhpDbTestAsset\Pgsql\SetupTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableGateway::class)]
final class TableGatewayTest extends TestCase
{
    use SetupTrait;

    #[Test]
    public function lastInsertValue(): void
    {
        $table      = new TableIdentifier('test_seq');
        $featureSet = new FeatureSet();
        $featureSet->addFeature(new SequenceFeature('id', 'test_seq_id_seq'));

        $tableGateway = new TableGateway($table, $this->getAdapter(self::PDO_ADAPTER), $featureSet);

        $tableGateway->insert(['foo' => 'bar']);
        static::assertSame(1, $tableGateway->getLastInsertValue());

        $tableGateway->insert(['foo' => 'baz']);
        static::assertSame(2, $tableGateway->getLastInsertValue());
    }
}
