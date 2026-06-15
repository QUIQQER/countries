<?php

/**
 * This file contains package_quiqqer_countries_ajax_unSelectAllCountries
 */

use QUI\Countries\Manager;

/**
 * Unselect all countries
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_countries_ajax_unSelectAllCountries',
    function () {
        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(Manager::getDataBaseTableName()),
            ['active' => 0],
            []
        );

        QUI\Cache\Manager::clear('quiqqer/countries');
    },
    false,
    'Permission::checkSU'
);
