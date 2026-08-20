<?php

namespace QUITests\QUI\Countries;

require_once __DIR__ . '/SqliteDatabaseTestCase.php';
require_once __DIR__ . '/TestableManager.php';

use QUI;
use QUI\Countries\Country;
use QUI\Countries\Manager;

class ManagerTest extends SqliteDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createCountriesFixture();
    }

    public function testTableNameAndCountryTypeDetection(): void
    {
        $Country = Manager::get(self::FIXTURE_COUNTRY_CODE_1);

        $this->assertSame(QUI::getDBTableName('countries'), Manager::getDataBaseTableName());
        $this->assertTrue(Manager::isCountry($Country));
        $this->assertFalse(Manager::isCountry(null));
        $this->assertFalse(Manager::isCountry(new \stdClass()));
    }

    public function testGetUsesRequestedCodeTypeAndCachesResult(): void
    {
        $Country = Manager::get('XQQ', 'countries_iso_code_3');

        $this->assertSame(self::FIXTURE_COUNTRY_CODE_1, $Country->getCode());
        $this->assertSame($Country, Manager::get('XQQ', 'countries_iso_code_3'));
    }

    public function testGetRejectsUnsupportedCodeType(): void
    {
        $this->expectException(QUI\Exception::class);
        $this->expectExceptionCode(404);

        Manager::get(self::FIXTURE_COUNTRY_CODE_1, 'numeric_code');
    }

    public function testDefaultCountryIsStableAndUsable(): void
    {
        $DefaultCountry = Manager::getDefaultCountry();

        $this->assertSame($DefaultCountry, Manager::getDefaultCountry());
        $this->assertSame(2, strlen($DefaultCountry->getCode()));
    }

    public function testCountryListsContainCountriesInCodeOrder(): void
    {
        $activeCountries = Manager::getList();
        $completeCountries = Manager::getCompleteList();

        $this->assertNotEmpty($activeCountries);
        $this->assertGreaterThanOrEqual(count($activeCountries), count($completeCountries));
        $this->assertContainsOnlyInstancesOf(Country::class, $activeCountries);
        $this->assertContainsOnlyInstancesOf(Country::class, $completeCountries);

        $activeCodes = array_map(
            static fn (Country $Country): string => $Country->getCode(),
            $activeCountries
        );
        $sortedCodes = $activeCodes;
        sort($sortedCodes, SORT_STRING);

        $this->assertSame($sortedCodes, $activeCodes);
        $this->assertSame(
            [],
            array_filter(
                $activeCountries,
                static fn (Country $Country): bool => (int)$Country->getAttribute('active') !== 1
            )
        );
    }

    public function testSortedListUsesCountryNamesByDefault(): void
    {
        $countries = Manager::getSortedList();
        $names = array_map(static fn (Country $Country): string => $Country->getName(), $countries);
        $expectedNames = $names;
        usort($expectedNames, 'strnatcmp');

        $this->assertNotEmpty($countries);
        $this->assertSame($expectedNames, $names);
    }

    public function testSortedListAcceptsCallableAndCompleteOption(): void
    {
        $descending = static fn (Country $CountryA, Country $CountryB): int => strcmp(
            $CountryB->getCode(),
            $CountryA->getCode()
        );
        $descendingCountries = Manager::getSortedList($descending);
        $descendingCodes = array_map(
            static fn (Country $Country): string => $Country->getCode(),
            $descendingCountries
        );
        $expectedDescendingCodes = $descendingCodes;
        rsort($expectedDescendingCodes, SORT_STRING);

        $this->assertSame($expectedDescendingCodes, $descendingCodes);

        $completeCountries = Manager::getSortedList([
            'complete' => true,
            'sort' => static fn (Country $CountryA, Country $CountryB): int => strcmp(
                $CountryA->getCode(),
                $CountryB->getCode()
            )
        ]);
        $completeCodes = array_map(
            static fn (Country $Country): string => $Country->getCode(),
            $completeCountries
        );
        $expectedCompleteCodes = $completeCodes;
        sort($expectedCompleteCodes, SORT_STRING);

        $this->assertCount(count(Manager::getCompleteList()), $completeCountries);
        $this->assertSame($expectedCompleteCodes, $completeCodes);
    }

    public function testAllCountryCodesAndExistenceChecksReflectActiveCountries(): void
    {
        $expectedCodes = array_map(
            static fn (Country $Country): string => $Country->getCode(),
            Manager::getList()
        );

        $this->assertSame($expectedCodes, Manager::getAllCountryCodes());
        $this->assertTrue(Manager::existsCountryCode(self::FIXTURE_COUNTRY_CODE_1));
        $this->assertFalse(Manager::existsCountryCode('__'));
    }

    public function testDatabaseRowsAreParsedCachedAndInvalidRowsAreIgnored(): void
    {
        $validRow = [
            'countries_id' => 999,
            'countries_name' => 'Test country',
            'countries_iso_code_2' => 'XQ',
            'countries_iso_code_3' => 'XQQ',
            'numeric_code' => '999',
            'language' => 'en',
            'languages' => '[{"language":"en","percent":"100"}]',
            'currency' => 'EUR',
            'active' => 1
        ];
        $invalidRow = $validRow;
        $invalidRow['countries_iso_code_2'] = 'XR';
        unset($invalidRow['currency']);

        $countries = TestableManager::parseRows([$validRow, $validRow, $invalidRow]);

        $this->assertCount(2, $countries);
        $this->assertSame($countries[0], $countries[1]);
        $this->assertSame('XQ', $countries[0]->getCode());
    }
}
