<?php

namespace QUITests\QUI\Countries;

use PHPUnit\Framework\TestCase;
use QUI;

class CountryTest extends TestCase
{
    public function testCountry(): void
    {
        $Country = QUI\Countries\Manager::get('de');

        $this->assertNotEmpty($Country);
        $this->assertNotEmpty($Country->getCode());
        $this->assertNotEmpty($Country->getName());

        $this->expectException(QUI\Exception::class);
        QUI\Countries\Manager::get('__');
    }

    public function testConstruct(): void
    {
        $Country = QUI\Countries\Manager::get('nl');
        $this->assertSame('NL', $Country->getCode());
    }

    public function testGetCode(): void
    {
        $Country = QUI\Countries\Manager::get('gb');

        if ($Country->getAttribute('countries_iso_code_2')) {
            $this->assertSame('GB', $Country->getCode('countries_iso_code_2'));
        }

        if ($Country->getAttribute('countries_iso_code_3')) {
            $this->assertSame('GBR', $Country->getCode('countries_iso_code_3'));
        }
    }

    public function testGetCurrencyCode(): void
    {
        $Country = QUI\Countries\Manager::get('de');
        $currency = $Country->getCurrencyCode();

        $this->assertSame('EUR', $currency);
    }

    public function testGetCurrency(): void
    {
        $Country = QUI\Countries\Manager::get('de');
        $Currency = $Country->getCurrency();

        $this->assertSame('EUR', $Currency->getCode());
    }

    public function testGetName(): void
    {
        $Country = QUI\Countries\Manager::get('pl');
        $code = $Country->getAttribute('countries_iso_code_2');
        $name = $Country->getName();

        $localeVar = 'country.' . $code;

        if (QUI::getLocale()->exists('quiqqer/countries', $localeVar)) {
            $this->assertSame(QUI::getLocale()->get('quiqqer/countries', $localeVar), $name);
        }

        $this->assertSame('Poland', $name);
    }

    public function testGetLanguages(): void
    {
        $Country = QUI\Countries\Manager::get('de');

        $this->assertNotEmpty($Country->getLanguages());
    }
}
