<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Pgsql;

use PhpDb\Adapter\Exception\VunerablePlatformQuoteException;
use PhpDb\Pgsql\AdapterPlatform;
use PhpDbTestAsset\Pgsql\SetupTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdapterPlatform::class)]
#[CoversMethod(AdapterPlatform::class, 'quoteValue')]
final class AdapterPlatformTest extends TestCase
{
    use SetupTrait;

    public function testQuoteValueWithPdoPgsql(): void
    {
        $adapter = $this->getAdapter(self::PDO_ADAPTER);
        $pgsql   = $adapter->getPlatform();
        //$this->expectException(VunerablePlatformQuoteException::class);
        $value = $pgsql->quoteValue('value');
        self::assertEquals('\'value\'', $value);
    }

    public function testQuoteValueWithPgsql(): void
    {
        $adapter = $this->getAdapter(self::NATIVE_ADAPTER);
        $pgsql   = $adapter->getPlatform();
        $this->expectException(VunerablePlatformQuoteException::class);
        $value = $pgsql->quoteValue('value');
        self::assertEquals('\'value\'', $value);

        // Should this assertion be?
        //todo:  self::assertEquals('E\'value\'', $value);
    }
}
