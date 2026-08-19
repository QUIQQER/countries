<?php

namespace QUITests\QUI\Countries;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Countries\Country;

class CountryTest extends TestCase
{
    #[DataProvider('provideMissingConstructorParameters')]
    public function testConstructorRejectsMissingRequiredParameters(array $params, string $message): void
    {
        $this->expectException(QUI\Exception::class);
        $this->expectExceptionMessage($message);

        new Country($params);
    }

    public static function provideMissingConstructorParameters(): array
    {
        $complete = self::getCountryData();
        $cases = [];

        foreach (
            [
                'countries_iso_code_2',
                'countries_iso_code_3',
                'countries_id',
                'language',
                'languages',
                'currency'
            ] as $requiredParameter
        ) {
            $params = $complete;
            unset($params[$requiredParameter]);
            $cases[$requiredParameter] = [$params, 'Parameter ' . $requiredParameter . ' fehlt'];
        }

        return $cases;
    }

    public function testCodesAndLocaleCodeUseRequestedFormat(): void
    {
        $Country = new Country(self::getCountryData());

        $this->assertSame('DE', $Country->getCode());
        $this->assertSame('DE', $Country->getCode('unsupported'));
        $this->assertSame('DEU', $Country->getCode('countries_iso_code_3'));
        $this->assertSame('de', $Country->getCodeToLower());
        $this->assertSame('deu', $Country->getCodeToLower('countries_iso_code_3'));
        $this->assertSame('de_DE', $Country->getLocaleCode());
    }

    public function testCurrencyCodeIsReturned(): void
    {
        $Country = new Country(self::getCountryData());

        $this->assertSame('EUR', $Country->getCurrencyCode());
    }

    public function testNameUsesTranslationAndFallsBackToStoredName(): void
    {
        $Locale = new QUI\Locale();
        $Locale->setCurrent('en');
        $Country = new Country(self::getCountryData());

        $this->assertSame('Germany', $Country->getName($Locale));

        $data = self::getCountryData();
        $data['countries_iso_code_2'] = 'XQ';
        $data['countries_name'] = 'Fallback country';
        $UnknownCountry = new Country($data);

        $this->assertSame('Fallback country', $UnknownCountry->getName($Locale));
    }

    public function testLanguagesContainOnlyValidLanguageStrings(): void
    {
        $data = self::getCountryData();
        $data['languages'] = json_encode([
            ['language' => 'de', 'percent' => '80'],
            ['percent' => '10'],
            ['language' => 123, 'percent' => '5'],
            ['language' => 'en', 'percent' => '5']
        ], JSON_THROW_ON_ERROR);
        $Country = new Country($data);

        $this->assertSame(['de', 'en'], $Country->getLanguages());
        $this->assertSame('de', $Country->getLang());
    }

    public function testInvalidLanguageJsonProducesEmptyLanguageList(): void
    {
        $data = self::getCountryData();
        $data['languages'] = 'not-json';

        $this->assertSame([], (new Country($data))->getLanguages());
    }

    public function testEuropeanUnionMembershipDistinguishesMemberAndNonMember(): void
    {
        $GermanCountry = new Country(self::getCountryData());
        $britishData = self::getCountryData();
        $britishData['countries_iso_code_2'] = 'GB';
        $BritishCountry = new Country($britishData);

        $this->assertTrue($GermanCountry->isEU());
        $this->assertFalse($BritishCountry->isEU());
    }

    private static function getCountryData(): array
    {
        return [
            'countries_id' => 1,
            'countries_name' => 'Germany',
            'countries_iso_code_2' => 'DE',
            'countries_iso_code_3' => 'DEU',
            'numeric_code' => '276',
            'language' => 'de',
            'languages' => '[{"language":"de","percent":"100"}]',
            'currency' => 'EUR',
            'active' => 1
        ];
    }
}
