<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

require_once __DIR__ . '/QUITests/QUI/Countries/DatabaseEnvironment.php';
require_once __DIR__ . '/../../../../bootstrap.php';

if (QUITests\QUI\Countries\DatabaseEnvironment::usesCiDatabase()) {
    $databasePlatform = QUI::getDataBaseConnection()->getDatabasePlatform();
    $databasePlatformClass = $databasePlatform::class;
    $databaseVendor = QUITests\QUI\Countries\DatabaseEnvironment::getCiVendor();

    if (!$databasePlatform instanceof Doctrine\DBAL\Platforms\AbstractMySQLPlatform) {
        throw new RuntimeException(
            'GitLab countries tests expected a MySQL-compatible DBAL platform, got ' . $databasePlatformClass . '.'
        );
    }

    $isMariaDbPlatform = str_contains(strtolower($databasePlatformClass), 'maria');

    if (
        ($databaseVendor === 'mariadb' && !$isMariaDbPlatform)
        || ($databaseVendor === 'mysql' && $isMariaDbPlatform)
    ) {
        throw new RuntimeException(
            'GitLab DB_VENDOR=' . $databaseVendor . ' does not match DBAL platform ' . $databasePlatformClass . '.'
        );
    }
}
