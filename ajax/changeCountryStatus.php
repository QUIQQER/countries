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
        $Country = Manager::get($code);

        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(Manager::getDataBaseTableName()),
            ['active' => (int)$status],
            ['countries_iso_code_2' => $Country->getCode()]
        );

        QUI\Cache\Manager::clear('quiqqer/countries');
    },
    ['code', 'status'],
    'Permission::checkSU'
);
