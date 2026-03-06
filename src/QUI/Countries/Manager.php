<?php

/**
 * This file contains the \QUI\Countries\Manager
 */

namespace QUI\Countries;

use QUI;

use function get_class;
use function is_array;
use function strnatcmp;
use function usort;

/**
 * Country Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package QUI\Countries
 */
class Manager extends QUI\QDOM
{
    /**
     * country stack
     *
     * @var array<string, Country>
     */
    private static array $countries = [];

    /**
     * @var ?Country
     */
    private static ?Country $DefaultCountry = null;

    /**
     * Return the real table name
     *
     * @return string
     */
    public static function getDataBaseTableName(): string
    {
        return QUI::getDBTableName('countries');
    }

    /**
     * Checks if $Mixed is a country
     *
     * @param mixed $Mixed
     * @return boolean
     */
    public static function isCountry(mixed $Mixed): bool
    {
        if (!$Mixed) {
            return false;
        }

        return get_class($Mixed) == Country::class;
    }

    /**
     * Return the default country
     *
     * @return Country|null
     * @throws QUI\Exception
     */
    public static function getDefaultCountry(): ?Country
    {
        if (self::$DefaultCountry === null) {
            try {
                self::$DefaultCountry = QUI\Countries\Manager::get(
                    QUI::conf('globals', 'country')
                );
            } catch (QUI\Exception) {
                self::$DefaultCountry = QUI\Countries\Manager::get('de');
            }
        }

        return self::$DefaultCountry;
    }

    /**
     * Get a country
     *
     * @param String $code - the country code
     * @param string $type (optional) - type of code ("countries_iso_code_2" or "countries_iso_code_3")
     *
     * @return QUI\Countries\Country
     * @throws QUI\Exception
     * @example
     * $Country = \QUI\Countries\Manager::get('de');
     * $Country->getName()
     */
    public static function get(string $code, string $type = 'countries_iso_code_2'): Country
    {
        if (isset(self::$countries[$code])) {
            return self::$countries[$code];
        }

        $result = QUI::getDataBase()->fetch([
            'from' => self::getDataBaseTableName(),
            'where' => [
                $type => QUI\Utils\StringHelper::toUpper($code)
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/countries', 'exception.country.not.found'),
                404
            );
        }

        self::$countries[$code] = new Country($result[0]);

        return self::$countries[$code];
    }

    /**
     * Returns the complete country list
     *
     * @return list<Country>
     */
    public static function getCompleteList(): array
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'from' => self::getDataBaseTableName(),
                'order' => 'countries_iso_code_2 ASC'
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return [];
        }

        return self::parseCountryDbData($result);
    }

    /**
     * Return ths country list
     * Only active countries
     *
     * @return list<Country>
     */
    public static function getList(): array
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'from' => self::getDataBaseTableName(),
                'where' => [
                    'active' => 1
                ],
                'order' => 'countries_iso_code_2 ASC'
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return [];
        }

        return self::parseCountryDbData($result);
    }

    /**
     * @param array<int, array<string, mixed>> $result
     * @return list<Country>
     */
    protected static function parseCountryDbData(array $result): array
    {
        $countries = [];

        foreach ($result as $entry) {
            $code = $entry['countries_iso_code_2'];

            if (isset(self::$countries[$code])) {
                $countries[] = self::$countries[$code];
                continue;
            }

            try {
                $Country = new Country($entry);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                continue;
            }

            self::$countries[$code] = $Country;

            $countries[] = $Country;
        }

        return $countries;
    }

    /**
     * Return the countries in sorted order
     *
     * @param (callable(Country, Country): int)|array{sort?: callable(Country, Country): int, complete?: bool}|null $params
     * @return list<Country>
     */
    public static function getSortedList(null|callable|array $params = null): array
    {
        $complete = false;
        $sort = null;

        if (!is_array($params)) {
            $sort = $params;
        } else {
            if (isset($params['sort'])) {
                $sort = $params['sort'];
            }

            if (isset($params['complete'])) {
                $complete = $params['complete'];
            }
        }

        if ($complete) {
            $countries = self::getCompleteList();
        } else {
            $countries = self::getList();
        }

        if ($sort === null) {
            $sort = function ($CountryA, $CountryB) {
                /* @var $CountryA Country */
                /* @var $CountryB Country */
                return strnatcmp($CountryA->getName(), $CountryB->getName());
            };
        }

        usort($countries, $sort);

        return $countries;
    }

    /**
     * Return all available country codes
     *
     * @return list<string>
     */
    public static function getAllCountryCodes(): array
    {
        $result = [];
        $countries = self::getList();

        foreach ($countries as $Country) {
            /* @var $Country Country */
            $result[] = $Country->getCode();
        }

        return $result;
    }

    /**
     * Exist the country code in the database?
     *
     * @param string $code - Country code
     *
     * @return bool
     */
    public static function existsCountryCode(string $code): bool
    {
        try {
            self::get($code);

            return true;
        } catch (QUI\Exception) {
        }

        return false;
    }
}
