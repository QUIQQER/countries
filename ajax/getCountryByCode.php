<?php

/**
 * Get data of specific country
 *
 * @param string $countryCode - country code (ISO 3166-1 alpha-2 or ISO 3166-1 alpha-3)
 * @return array
 */

use QUI\Countries\Manager;
use QUI\Utils\Security\Orthos;

QUI::getAjax()->registerFunction(
    'package_quiqqer_countries_ajax_getCountryByCode',
    function ($countryCode) {
        $countryCode = Orthos::clear($countryCode);
        $codeType = 'countries_iso_code_2';

        if (mb_strlen($countryCode) > 2) {
            $codeType = 'countries_iso_code_3';
        }

        $Country = Manager::get($countryCode, $codeType);

        return $Country->getAttributes();
    },
    ['countryCode']
);
