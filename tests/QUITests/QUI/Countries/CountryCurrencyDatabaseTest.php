<?php

namespace QUITests\QUI\Countries;

require_once __DIR__ . '/SqliteDatabaseTestCase.php';

use QUI\Countries\Country;
use QUI\ERP\Currency\Handler as CurrencyHandler;
use QUI\Update;

class CountryCurrencyDatabaseTest extends SqliteDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Update::importDatabase(OPT_DIR . 'quiqqer/currency/database.xml');
        $this->connection->insert(CurrencyHandler::table(), [
            'currency' => 'EUR',
            'rate' => 1,
            'autoupdate' => 0,
            'precision' => 2,
            'type' => CurrencyHandler::CURRENCY_TYPE_DEFAULT,
            'customData' => null
        ]);
    }

    public function testConfiguredCurrencyIsLoadedFromSqlite(): void
    {
        $Country = $this->createCountry('EUR');

        $this->assertSame('EUR', $Country->getCurrency()->getCode());
    }

    public function testUnknownCurrencyFallsBackToSqliteDefaultCurrency(): void
    {
        $DefaultCurrency = CurrencyHandler::getDefaultCurrency();
        $this->assertNotNull($DefaultCurrency);

        $this->assertSame($DefaultCurrency, $this->createCountry('__UNKNOWN__')->getCurrency());
    }

    private function createCountry(string $currency): Country
    {
        return new Country([
            'countries_id' => 0,
            'countries_name' => 'Test country',
            'countries_iso_code_2' => 'XQ',
            'countries_iso_code_3' => 'XQQ',
            'numeric_code' => '999',
            'language' => 'en',
            'languages' => '[{"language":"en","percent":"100"}]',
            'currency' => $currency,
            'active' => 1
        ]);
    }
}
