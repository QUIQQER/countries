<?php

namespace QUITests\QUI\Countries;

require_once __DIR__ . '/SqliteDatabaseTestCase.php';

use QUI;
use QUI\Countries\Manager;
use QUI\Countries\Setup;

class SetupTest extends SqliteDatabaseTestCase
{
    private QUI\Config $Config;
    private mixed $originalDataMd5;

    protected function setUp(): void
    {
        parent::setUp();

        $Config = QUI::getPackage('quiqqer/countries')->getConfig();
        $this->assertNotNull($Config);

        $this->Config = $Config;
        $this->originalDataMd5 = $Config->getValue('general', 'dataMd5');
    }

    protected function tearDown(): void
    {
        try {
            $this->Config->setValue('general', 'dataMd5', $this->originalDataMd5);
            $this->Config->save();
        } finally {
            parent::tearDown();
        }
    }

    public function testSetupReimportsCountryDataAndIsIdempotent(): void
    {
        Setup::setup();

        $expectedMd5 = md5_file(dirname(__DIR__, 4) . '/db/intl.json');
        $this->assertNotFalse($expectedMd5);
        $this->assertSame($expectedMd5, $this->Config->getValue('general', 'dataMd5'));

        $table = Manager::getDataBaseTableName();
        $Table = QUI::getSchemaManager()->introspectTable($table);

        foreach (
            [
                'countries_id',
                'countries_name',
                'countries_iso_code_2',
                'countries_iso_code_3',
                'numeric_code',
                'language',
                'languages',
                'currency',
                'active'
            ] as $column
        ) {
            $this->assertTrue($Table->hasColumn($column), 'Missing country table column: ' . $column);
        }

        $QueryBuilder = QUI::getQueryBuilder();
        $countryCount = (int)$QueryBuilder
            ->select('COUNT(*)')
            ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
            ->executeQuery()
            ->fetchOne();

        $this->assertGreaterThan(200, $countryCount);

        Setup::setup();

        $this->assertSame($expectedMd5, $this->Config->getValue('general', 'dataMd5'));
        $this->assertSame(
            $countryCount,
            (int)QUI::getQueryBuilder()
                ->select('COUNT(*)')
                ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
                ->executeQuery()
                ->fetchOne()
        );
    }
}
