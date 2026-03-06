<?php

/**
 * This file contains package_quiqqer_countries_ajax_changeCountryStatus
 */

use QUI\Countries\Manager;

/**
 * Save the country status (active or inactive)
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_countries_ajax_changeCountryStatus',
    function ($code, $status) {
        // check if country exists
        Manager::get($code);

        QUI::getDataBase()->update(
            Manager::getDataBaseTableName(),
            ['active' => $status],
            ['countries_iso_code_2' => $code]
        );

        QUI\Cache\Manager::clear('quiqqer/countries');
    },
    ['code', 'status'],
    'Permission::checkSU'
);
