<?php

namespace QUITests\QUI\Countries;

require_once __DIR__ . '/SqliteDatabaseTestCase.php';

use QUI\Countries\Controls\Select;
use QUI\Countries\Manager;

class SelectTest extends SqliteDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createCountriesFixture();
    }

    public function testConstructorProvidesDocumentedDefaults(): void
    {
        $Select = new Select();

        $this->assertSame('countries', $Select->getAttribute('name'));
        $this->assertSame('', $Select->getAttribute('selected'));
        $this->assertFalse($Select->getAttribute('class'));
        $this->assertFalse($Select->getAttribute('required'));
        $this->assertTrue($Select->getAttribute('use-geo-location'));
    }

    public function testCreateRendersConfiguredSelectAndSelectedOption(): void
    {
        $Select = new Select([
            'name' => 'billing-country',
            'class' => 'country-input',
            'required' => true,
            'selected' => self::FIXTURE_COUNTRY_CODE_1,
            'use-geo-location' => false
        ]);

        $html = $Select->create();

        $this->assertStringStartsWith(
            '<select data-qui="package/quiqqer/countries/bin/controls/Select"',
            $html
        );
        $this->assertStringContainsString(' name="billing-country"', $html);
        $this->assertStringContainsString(' class="country-input"', $html);
        $this->assertStringContainsString(' required', $html);
        $this->assertStringContainsString(' autocomplete="country-name"', $html);
        $this->assertMatchesRegularExpression(
            '/<option value="' . self::FIXTURE_COUNTRY_CODE_1 . '" selected="selected">[^<]+<\/option>/',
            $html
        );
        $this->assertStringEndsWith('</select>', $html);
    }

    public function testCreateCanOmitNameAndDisableAutocomplete(): void
    {
        $Select = new Select([
            'name' => '',
            'class' => false,
            'required' => false,
            'selected' => self::FIXTURE_COUNTRY_CODE_2,
            'use-geo-location' => false,
            'no-autocomplete' => true
        ]);

        $html = $Select->create();

        $this->assertStringNotContainsString(' name=', $html);
        $this->assertStringNotContainsString(' class=', $html);
        $this->assertStringNotContainsString(' required', $html);
        $this->assertStringContainsString(' autocomplete="off"', $html);
        $this->assertStringContainsString(
            '<option value="' . self::FIXTURE_COUNTRY_CODE_2 . '" selected="selected">',
            $html
        );
    }

    public function testCreateUsesGeoIpCountryWhenNoSelectionWasProvided(): void
    {
        $hadGeoIpCode = array_key_exists('GEOIP_COUNTRY_CODE', $_SERVER);
        $previousGeoIpCode = $_SERVER['GEOIP_COUNTRY_CODE'] ?? null;
        $_SERVER['GEOIP_COUNTRY_CODE'] = self::FIXTURE_COUNTRY_CODE_3;

        try {
            $html = (new Select())->create();
        } finally {
            if ($hadGeoIpCode) {
                $_SERVER['GEOIP_COUNTRY_CODE'] = $previousGeoIpCode;
            } else {
                unset($_SERVER['GEOIP_COUNTRY_CODE']);
            }
        }

        $this->assertStringContainsString(
            '<option value="' . self::FIXTURE_COUNTRY_CODE_3 . '" selected="selected">',
            $html
        );
    }

    public function testCreateFallsBackToDefaultCountryForUnknownGeoIpCode(): void
    {
        $hadGeoIpCode = array_key_exists('GEOIP_COUNTRY_CODE', $_SERVER);
        $previousGeoIpCode = $_SERVER['GEOIP_COUNTRY_CODE'] ?? null;
        $_SERVER['GEOIP_COUNTRY_CODE'] = '__';

        try {
            $html = (new Select())->create();
        } finally {
            if ($hadGeoIpCode) {
                $_SERVER['GEOIP_COUNTRY_CODE'] = $previousGeoIpCode;
            } else {
                unset($_SERVER['GEOIP_COUNTRY_CODE']);
            }
        }

        $defaultCode = Manager::getDefaultCountry()->getCode();
        $this->assertStringContainsString(
            '<option value="' . $defaultCode . '" selected="selected">',
            $html
        );
    }

    public function testCreateEscapesDynamicHtmlAttributes(): void
    {
        $Select = new Select([
            'name' => '"><script>alert(1)</script>',
            'class' => 'country" autofocus="autofocus',
            'selected' => self::FIXTURE_COUNTRY_CODE_1,
            'use-geo-location' => false
        ]);

        $html = $Select->create();

        $this->assertStringContainsString(
            'name="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"',
            $html
        );
        $this->assertStringContainsString('class="country', $html);
        $this->assertStringNotContainsString(' autofocus="autofocus"', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
