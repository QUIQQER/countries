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
    protected Connection $connection;
    private Connection $originalConnection;

    /** @var array<string, mixed> */
    private array $originalCountriesState;

    /** @var array<string, mixed> */
    private array $originalCurrencyState;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = QUI::getDataBaseConnection();
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);
        $this->originalCountriesState = $this->getStaticState(
            Manager::class,
            ['countries', 'DefaultCountry']
        );
        $this->originalCurrencyState = $this->getStaticState(
            CurrencyHandler::class,
            ['currencies', 'Default', 'RuntimeCurrency']
        );

        $this->setConnection($this->connection);
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
        $this->setConnection($this->originalConnection);
        $this->setStaticState(Manager::class, $this->originalCountriesState);
        $this->setStaticState(CurrencyHandler::class, $this->originalCurrencyState);
        $this->connection->close();

        parent::tearDown();
    }

    protected function createCountriesFixture(): void
    {
        $table = Manager::getDataBaseTableName();
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

        foreach (
            [
                ['Germany', 'DE', 'DEU', '276', 'de', 'EUR', 1],
                ['United Kingdom', 'GB', 'GBR', '826', 'en', 'GBP', 1],
                ['Poland', 'PL', 'POL', '616', 'pl', 'PLN', 1],
                ['France', 'FR', 'FRA', '250', 'fr', 'EUR', 0]
            ] as [$name, $code2, $code3, $numericCode, $language, $currency, $active]
        ) {
            $this->connection->insert($table, [
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
    }

    private function setConnection(Connection $Connection): void
    {
        (new ReflectionProperty(QUI::class, 'QueryBuilder'))->setValue(null, $Connection);
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
