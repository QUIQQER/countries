<?php

/**
 * This file contains package_quiqqer_countries_ajax_selectAllCountries
 */

use QUI\Countries\Manager;

/**
 * Select all countries
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_countries_ajax_selectAllCountries',
    function () {
        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(Manager::getDataBaseTableName()),
            ['active' => 1],
            []
        );

        QUI\Cache\Manager::clear('quiqqer/countries');
    },
    false,
    'Permission::checkSU'
);
