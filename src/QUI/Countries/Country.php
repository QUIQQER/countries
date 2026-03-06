<?php

/**
 * This file contains the \QUI\Countries\Country
 */

namespace QUI\Countries;

use QUI;
use QUI\Exception;

use function array_map;
use function is_array;
use function is_null;
use function is_string;
use function json_decode;
use function strtolower;
use function strtoupper;

/**
 * A Country
 */
class Country extends QUI\QDOM
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $languages = [];

    /**
     * constructor
     * If you want a country, use the manager
     *
     * @param array<string, mixed> $params
     *
     * @throws QUI\Exception
     * @example
     * $Country = \QUI\Countries\Manager::get('de');
     * $Country->getName()
     *
     */
    public function __construct(array $params = [])
    {
        if (!isset($params['countries_iso_code_2'])) {
            throw new QUI\Exception('Parameter countries_iso_code_2 fehlt');
        }

        if (!isset($params['countries_iso_code_3'])) {
            throw new QUI\Exception('Parameter countries_iso_code_3 fehlt');
        }

        if (!isset($params['countries_id'])) {
            throw new QUI\Exception('Parameter countries_id fehlt');
        }

        if (!isset($params['language'])) {
            throw new QUI\Exception('Parameter language fehlt');
        }

        if (!isset($params['languages'])) {
            throw new QUI\Exception('Parameter languages fehlt');
        }

        if (!isset($params['currency'])) {
            throw new QUI\Exception('Parameter currency fehlt');
        }

        if (is_string($params['languages'])) {
            $decodedLanguages = json_decode($params['languages'], true);

            if (is_array($decodedLanguages)) {
                $this->languages = $decodedLanguages;
            }
        }

        parent::setAttributes($params);
    }

    /**
     * Return the country code
     * iso_code_2 or iso_code_2
     *
     * @param string $type [$type] - countries_iso_code_2 or countries_iso_code_3
     *
     * @return string
     */
    public function getCode(string $type = 'countries_iso_code_2'): string
    {
        switch ($type) {
            default:
            case 'countries_iso_code_2':
                return $this->getAttribute('countries_iso_code_2');

            case 'countries_iso_code_3':
                return $this->getAttribute('countries_iso_code_3');
        }
    }

    /**
     * Return the country code in lowercase
     *
     * @param string $type
     * @return string
     */
    public function getCodeToLower(string $type = 'countries_iso_code_2'): string
    {
        return strtolower($this->getCode($type));
    }

    /**
     * Return the currency object
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->getAttribute('currency');
    }

    /**
     * Return the currency of the country
     *
     * @return QUI\ERP\Currency\Currency
     * @throws Exception
     */
    public function getCurrency(): QUI\ERP\Currency\Currency
    {
        // currency installed?
        QUI::getPackage('quiqqer/currency');

        try {
            return QUI\ERP\Currency\Handler::getCurrency(
                $this->getCurrencyCode()
            );
        } catch (QUI\Exception) {
        }

        $DefaultCurrency = QUI\ERP\Currency\Handler::getDefaultCurrency();

        if ($DefaultCurrency === null) {
            throw new Exception('No default currency available.');
        }

        return $DefaultCurrency;
    }

    /**
     * Return the name of the country
     * observed System_Locale
     *
     * @param QUI\Locale|null $Locale (optional) - Locale object that is used for the name translation [default: \QUI::getLocale()]
     * @return string
     */
    public function getName(null | QUI\Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        $localeVar = 'country.' . $this->getCode();

        if ($Locale->exists('quiqqer/countries', $localeVar)) {
            return $Locale->get('quiqqer/countries', $localeVar);
        }

        return $this->getAttribute('countries_name');
    }

    /**
     * Return the country language
     *
     * @return mixed
     */
    public function getLang(): mixed
    {
        return $this->getAttribute('language');
    }

    /**
     * Return all languages in the country
     *
     * @return list<string>
     */
    public function getLanguages(): array
    {
        return array_values(array_filter(array_map(function (array $data): string {
            if (!isset($data['language']) || !is_string($data['language'])) {
                return '';
            }

            return $data['language'];
        }, $this->languages)));
    }

    /**
     * Return the locale string of the country
     * en_US, en_GB, de_DE, de_AT
     *
     * @return string
     */
    public function getLocaleCode(): string
    {
        return strtolower($this->getLang()) . '_' . strtoupper($this->getCode());
    }

    /**
     * Is this country in the EU
     *
     * @return bool
     */
    public function isEU(): bool
    {
        switch ($this->getCode()) {
            case 'AT':
            case 'BE':
            case 'BG':
            case 'HR':
            case 'CY':
            case 'CZ':
            case 'DK':
            case 'EE':
            case 'FI':
            case 'FR':
            case 'DE':
            case 'GR':
            case 'HU':
            case 'IE':
            case 'IT':
            case 'LV':
            case 'LT':
            case 'LU':
            case 'MT':
            case 'NL':
            case 'PL':
            case 'PT':
            case 'RO':
            case 'SK':
            case 'SI':
            case 'ES':
            case 'SE':
                return true;
        }

        return false;
    }
}
