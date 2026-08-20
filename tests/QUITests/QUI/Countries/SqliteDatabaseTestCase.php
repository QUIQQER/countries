<?php

namespace QUITests\QUI\Countries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Countries\Manager;
use QUI\ERP\Currency\Handler as CurrencyHandler;
use ReflectionProperty;

abstract class SqliteDatabaseTestCase extends TestCase
{
    protected const FIXTURE_COUNTRY_CODE_1 = 'XQ';
    protected const FIXTURE_COUNTRY_CODE_2 = 'XR';
    protected const FIXTURE_COUNTRY_CODE_3 = 'XS';
    protected const FIXTURE_COUNTRY_CODE_INACTIVE = 'XT';
    protected const FIXTURE_CURRENCY_CODE = 'QCTST';

    private const FIXTURE_COUNTRY_NAME_PREFIX = '__quiqqer_countries_phpunit__';

    protected Connection $connection;
    private Connection $originalConnection;
    private bool $ownsTestConnection = false;

    /** @var array<string, mixed> */
    private array $originalCountriesState;

    /** @var array<string, mixed> */
    private array $originalCurrencyState;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (DatabaseEnvironment::usesCiDatabase()) {
            self::removeTestFixtures(QUI::getDataBaseConnection());
        }
    }

    public static function tearDownAfterClass(): void
    {
        try {
            if (DatabaseEnvironment::usesCiDatabase()) {
                self::removeTestFixtures(QUI::getDataBaseConnection());
            }
        } finally {
            parent::tearDownAfterClass();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = QUI::getDataBaseConnection();
        if (DatabaseEnvironment::usesCiDatabase()) {
            $this->connection = $this->originalConnection;
        } else {
            $this->connection = DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'memory' => true
            ]);
            $this->ownsTestConnection = true;
            $this->setConnection($this->connection);
        }

        self::removeTestFixtures($this->connection);
        $this->originalCountriesState = $this->getStaticState(
            Manager::class,
            ['countries', 'DefaultCountry']
        );
        $this->originalCurrencyState = $this->getStaticState(
            CurrencyHandler::class,
            ['currencies', 'Default', 'RuntimeCurrency']
        );
        $this->setStaticState(Manager::class, [
            'countries' => [],
            'DefaultCountry' => null
        ]);
        $this->setStaticState(CurrencyHandler::class, [
            'currencies' => [],
            'Default' => null,
            'RuntimeCurrency' => null
        ]);
    }

    protected function tearDown(): void
    {
        try {
            self::removeTestFixtures($this->connection);
        } finally {
            $this->setConnection($this->originalConnection);
            $this->setStaticState(Manager::class, $this->originalCountriesState);
            $this->setStaticState(CurrencyHandler::class, $this->originalCurrencyState);

            if ($this->ownsTestConnection) {
                $this->connection->close();
            }

            parent::tearDown();
        }
    }

    protected function createCountriesFixture(): void
    {
        $table = Manager::getDataBaseTableName();

        if ($this->ownsTestConnection) {
            $Table = new Table($table);
            $Table->addColumn('countries_id', 'integer', ['autoincrement' => true]);
            $Table->addColumn('countries_name', 'string', ['length' => 64]);
            $Table->addColumn('countries_iso_code_2', 'string', ['length' => 2]);
            $Table->addColumn('countries_iso_code_3', 'string', ['length' => 3]);
            $Table->addColumn('numeric_code', 'string', ['length' => 4]);
            $Table->addColumn('language', 'string', ['length' => 3]);
            $Table->addColumn('languages', 'text');
            $Table->addColumn('currency', 'string', ['length' => 3]);
            $Table->addColumn('active', 'smallint', ['default' => 1]);
            $Table->setPrimaryKey(['countries_id']);
            $this->connection->createSchemaManager()->createTable($Table);
        }

        foreach (self::getCountryFixtures() as [$name, $code2, $code3, $numericCode, $language, $currency, $active]) {
            $this->insertFixture($table, [
                'countries_name' => $name,
                'countries_iso_code_2' => $code2,
                'countries_iso_code_3' => $code3,
                'numeric_code' => $numericCode,
                'language' => $language,
                'languages' => json_encode([
                    ['language' => $language, 'percent' => '100']
                ], JSON_THROW_ON_ERROR),
                'currency' => $currency,
                'active' => $active
            ]);
        }

        $this->setStaticState(Manager::class, [
            'countries' => [],
            'DefaultCountry' => Manager::get(self::FIXTURE_COUNTRY_CODE_1)
        ]);
    }

    protected function usesLocalSqlite(): bool
    {
        return $this->ownsTestConnection;
    }

    /** @param array<string, mixed> $data */
    protected function insertFixture(string $table, array $data): void
    {
        $QueryBuilder = $this->connection->createQueryBuilder()
            ->insert($this->connection->quoteIdentifier($table));

        foreach ($data as $column => $value) {
            $parameter = 'value_' . $column;
            $QueryBuilder
                ->setValue($this->connection->quoteIdentifier($column), ':' . $parameter)
                ->setParameter($parameter, $value);
        }

        $QueryBuilder->executeStatement();
    }

    private function setConnection(Connection $Connection): void
    {
        (new ReflectionProperty(QUI::class, 'QueryBuilder'))->setValue(null, $Connection);
    }

    private static function removeTestFixtures(Connection $Connection): void
    {
        $SchemaManager = $Connection->createSchemaManager();
        $countriesTable = Manager::getDataBaseTableName();

        if ($SchemaManager->tablesExist([$countriesTable])) {
            foreach (self::getCountryFixtures() as [$name, $code2]) {
                $Connection->delete($countriesTable, [
                    'countries_name' => $name,
                    'countries_iso_code_2' => $code2
                ]);
            }
        }

        $currencyTable = CurrencyHandler::table();

        if ($SchemaManager->tablesExist([$currencyTable])) {
            $Connection->delete($currencyTable, [
                'currency' => self::FIXTURE_CURRENCY_CODE
            ]);
        }
    }

    /**
     * @return list<array{string, string, string, string, string, string, int}>
     */
    private static function getCountryFixtures(): array
    {
        return [
            [
                self::FIXTURE_COUNTRY_NAME_PREFIX . 'Germany',
                self::FIXTURE_COUNTRY_CODE_1,
                'XQQ',
                '9001',
                'de',
                'EUR',
                1
            ],
            [
                self::FIXTURE_COUNTRY_NAME_PREFIX . 'United Kingdom',
                self::FIXTURE_COUNTRY_CODE_2,
                'XRR',
                '9002',
                'en',
                'GBP',
                1
            ],
            [
                self::FIXTURE_COUNTRY_NAME_PREFIX . 'Poland',
                self::FIXTURE_COUNTRY_CODE_3,
                'XSS',
                '9003',
                'pl',
                'PLN',
                1
            ],
            [
                self::FIXTURE_COUNTRY_NAME_PREFIX . 'France',
                self::FIXTURE_COUNTRY_CODE_INACTIVE,
                'XTT',
                '9004',
                'fr',
                'EUR',
                0
            ]
        ];
    }

    /**
     * @param class-string $className
     * @param list<string> $properties
     * @return array<string, mixed>
     */
    private function getStaticState(string $className, array $properties): array
    {
        $state = [];

        foreach ($properties as $property) {
            $state[$property] = (new ReflectionProperty($className, $property))->getValue();
        }

        return $state;
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $state
     */
    private function setStaticState(string $className, array $state): void
    {
        foreach ($state as $property => $value) {
            (new ReflectionProperty($className, $property))->setValue(null, $value);
        }
    }
}
