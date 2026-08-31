<?php

declare(strict_types=1);

namespace PhpDbTest\Pgsql;

use PhpDb\Adapter\AdapterInterface;
use PhpDbTestAsset\Pgsql\SetupTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversTrait(SetupTrait::class)]
final class SetupTest extends TestCase
{
    use SetupTrait;

    #[Test]
    public function adapterAbstractFactoryBuildsNativeDriver(): void
    {
        $adapter = $this->getAdapter();
        static::assertInstanceOf(AdapterInterface::class, $adapter);
    }

    #[Test]
    public function adapterInterfaceFactoryBuildsNativeDriver(): void
    {
        $adapter = $this->getAdapter(self::NATIVE_ADAPTER);
        static::assertInstanceOf(AdapterInterface::class, $adapter);
    }
}
