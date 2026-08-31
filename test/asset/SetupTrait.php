<?php

declare(strict_types=1);

namespace PhpDbTestAsset\Pgsql;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\ConfigProvider as PhpDbConfigProvider;
use PhpDb\Pgsql;
use Psr\Container\ContainerInterface;

use function getenv;

trait SetupTrait
{
    final public const NATIVE_ADAPTER = 'Pgsql\Adapter';
    final public const PDO_ADAPTER    = 'Pgsql\Pdo\Adapter';

    protected array $conn = [];
    protected ServiceManager $serviceManager;

    public function getAdapter(string $adapter = self::NATIVE_ADAPTER): AdapterInterface
    {
        $serviceManagerConfig = [];
        $serviceManagerConfig = ArrayUtils::merge(
            (new Pgsql\ConfigProvider())()['dependencies'],
            [
                'services' => [
                    'config' => $this->getTestConfig(),
                ],
            ],
        );
        $serviceManagerConfig = ArrayUtils::merge(
            $serviceManagerConfig,
            (new PhpDbConfigProvider())()['dependencies'],
        );
        $this->serviceManager = new ServiceManager($serviceManagerConfig);
        return $this->serviceManager->get($adapter);
    }

    public function getHostname(): string
    {
        return $this->conn['host'];
    }

    public function getMockedAdapter(string $adapter = self::NATIVE_ADAPTER): AdapterInterface
    {
        $driverMock    = $this->getMockBuilder(DriverInterface::class)->getMock();
        $pdoDriverMock = $this->getMockBuilder(PdoDriverInterface::class)->getMock();
        $testConfig    = [
            AdapterInterface::class => [
                PhpDbConfigProvider::NAMED_ADAPTER_KEY => [
                    self::NATIVE_ADAPTER => [
                        'driver'     => Pgsql\Driver::class,
                        'connection' => [
                            'host'     => (string) getenv('TESTS_PHPDB_PGSQL_HOSTNAME'),
                            'port'     => 5432,
                            'username' => (string) getenv('TESTS_PHPDB_PGSQL_USERNAME'),
                            'password' => (string) getenv('TESTS_PHPDB_PGSQL_PASSWORD'),
                            'database' => (string) getenv('TESTS_PHPDB_PGSQL_DATABASE'),
                        ],
                    ],
                    self::PDO_ADAPTER    => [
                        'driver'     => Pgsql\Pdo\Driver::class,
                        'connection' => [
                            'host'     => (string) getenv('TESTS_PHPDB_PGSQL_HOSTNAME'),
                            'port'     => 5432,
                            'username' => (string) getenv('TESTS_PHPDB_PGSQL_USERNAME'),
                            'password' => (string) getenv('TESTS_PHPDB_PGSQL_PASSWORD'),
                            'database' => (string) getenv('TESTS_PHPDB_PGSQL_DATABASE'),
                        ],
                    ],
                ],
            ],
            'dependencies'          => [
                'aliases'   => [
                    DriverInterface::class        => Pgsql\Driver::class,
                    'pgsql'                       => Pgsql\Driver::class,
                    'PgSQL'                       => Pgsql\Driver::class,
                    'Postgresql'                  => Pgsql\Driver::class,
                    'PostgreSQL'                  => Pgsql\Driver::class,
                    PdoDriverInterface::class     => Pgsql\Pdo\Driver::class,
                    'pdo_pgsql'                   => Pgsql\Pdo\Driver::class,
                    'PDO_pgsql'                   => Pgsql\Pdo\Driver::class,
                    'PDO_PgSQL'                   => Pgsql\Pdo\Driver::class,
                    'Pdo_PgSQL'                   => Pgsql\Pdo\Driver::class,
                    'PDO_postgresql'              => Pgsql\Pdo\Driver::class,
                    'pdo_postgresql'              => Pgsql\Pdo\Driver::class,
                    'PDO_Postgresql'              => Pgsql\Pdo\Driver::class,
                    'Pdo_Postgresql'              => Pgsql\Pdo\Driver::class,
                    'PDO_PostgreSQL'              => Pgsql\Pdo\Driver::class,
                    'pdo_PostgreSQL'              => Pgsql\Pdo\Driver::class,
                    ConnectionInterface::class    => Pgsql\Connection::class,
                    PdoConnectionInterface::class => Pgsql\Pdo\Connection::class,
                    PlatformInterface::class      => Pgsql\AdapterPlatform::class,
                ],
                'factories' => [
                    Pgsql\Driver::class     => static fn(ContainerInterface $container) => $driverMock,
                    Pgsql\Pdo\Driver::class => static fn(ContainerInterface $container) => $pdoDriverMock,
                ],
            ],
        ];

        $libConfig            = (new Pgsql\ConfigProvider())();
        $config               = ArrayUtils::merge($libConfig, $testConfig);
        $serviceManagerConfig = ArrayUtils::merge(
            $config['dependencies'],
            [
                'services' => [
                    'config' => $config,
                ],
            ],
        );

        $this->serviceManager = new ServiceManager($serviceManagerConfig);
        return $this->serviceManager->get($adapter);
    }

    public function getTestConfig(): array
    {
        return [
            AdapterInterface::class => [
                'driver'     => Pgsql\Driver::class,
                'connection' => $this->conn,
                // Named Adapter configurations
                PhpDbConfigProvider::NAMED_ADAPTER_KEY => [
                    self::NATIVE_ADAPTER => [
                        'driver'     => Pgsql\Driver::class,
                        'connection' => $this->conn,
                    ],
                    self::PDO_ADAPTER    => [
                        'driver'     => Pgsql\Pdo\Driver::class,
                        'connection' => $this->conn,
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        $conn = [
            'host'     => (string) getenv('TESTS_PHPDB_PGSQL_HOSTNAME'),
            'port'     => 5432,
            'username' => (string) getenv('TESTS_PHPDB_PGSQL_USERNAME'),
            'password' => (string) getenv('TESTS_PHPDB_PGSQL_PASSWORD'),
            'database' => (string) getenv('TESTS_PHPDB_PGSQL_DATABASE'),
        ];
        $this->conn = $conn;
        parent::setUp();
    }
}
