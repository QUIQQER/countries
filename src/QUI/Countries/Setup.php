<?php

/**
 * This file contains the \QUI\Countries\Setup
 */

namespace QUI\Countries;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use QUI;
use QUI\Exception;

use function explode;
use function file_get_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function md5_file;
use function str_replace;
use function strlen;

/**
 * Country setup
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package quiqqer/countries
 */
class Setup extends QUI\QDOM
{
    /**
     * Country setup
     * Import the database
     * @throws Exception
     */
    public static function setup(): void
    {
        $Config = QUI::getPackage('quiqqer/countries')->getConfig();

        if ($Config === null) {
            throw new Exception('Country setup failed: package config is unavailable.');
        }

        $dataMd5 = $Config->getValue('general', 'dataMd5');

        // Countries
        $path = str_replace('src/QUI/Countries/Setup.php', '', __FILE__);
        $file = $path . '/db/intl.json';
        $fileMd5 = md5_file($file);

        if ($fileMd5 === false) {
            throw new Exception('Country setup failed: could not create checksum for country data file.');
        }

        $table = Manager::getDataBaseTableName();
        $SchemaManager = QUI::getSchemaManager();

        if ($fileMd5 == $dataMd5 && $SchemaManager->tablesExist([$table])) {
            self::ensureActiveColumn($table);
            return;
        }

        $json = file_get_contents($path . '/db/intl.json');

        if ($json === false) {
            throw new Exception('Country setup failed: could not read country data file.');
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new Exception('Country setup failed: invalid country data payload.');
        }

        if ($SchemaManager->tablesExist([$table])) {
            $SchemaManager->dropTable($table);
        }

        self::createCountryTable($table);

        foreach ($data as $country => $entry) {
            $language = '';

            if (!isset($entry['numeric_code'])) {
                $entry['numeric_code'] = '';
            }

            if (!isset($entry['three_letter_code'])) {
                $entry['three_letter_code'] = '';
            }

            if (isset($entry['language'][0])) {
                $language = $entry['language'][0];
            }

            if (!isset($entry['currency_code'])) {
                $entry['currency_code'] = '';
            }

            if (strlen($language) > 3 && !str_contains($language, '_')) {
                continue;
            }

            if (strlen($language) > 3) {
                $language = explode('_', $language);
                $language = $language[0];
            }

            try {
                QUI::getDataBaseConnection()->insert(QUI\Utils\Doctrine::quoteIdentifier($table), [
                    'countries_name' => $country,
                    'countries_iso_code_2' => $country,
                    'countries_iso_code_3' => $entry['three_letter_code'],
                    'numeric_code' => $entry['numeric_code'],
                    'language' => $language,
                    'languages' => json_encode($entry['languages']),
                    'currency' => $entry['currency_code']
                ]);
            } catch (QUI\Database\Exception $Exception) {
                QUI\System\Log::addWarning($Exception->getMessage());
            } catch (\Doctrine\DBAL\Exception $Exception) {
                QUI\System\Log::addWarning($Exception->getMessage());
            }
        }

        $Config->setValue('general', 'dataMd5', $fileMd5);
        $Config->save();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private static function ensureActiveColumn(string $table): void
    {
        $SchemaManager = QUI::getSchemaManager();
        $Table = $SchemaManager->introspectTable($table);

        if ($Table->hasColumn('active')) {
            return;
        }

        $SchemaManager->alterTable(new TableDiff(
            $Table,
            addedColumns: [
                new Column('active', Type::getType('smallint'), ['default' => 1])
            ]
        ));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private static function createCountryTable(string $table): void
    {
        $Table = new Table($table);
        $Table->addColumn('countries_id', 'integer', ['autoincrement' => true]);
        $Table->addColumn('countries_name', 'string', ['length' => 64]);
        $Table->addColumn('countries_iso_code_2', 'string', ['length' => 2]);
        $Table->addColumn('countries_iso_code_3', 'string', ['length' => 3]);
        $Table->addColumn('numeric_code', 'string', ['length' => 4]);
        $Table->addColumn('language', 'string', ['length' => 3]);
        $Table->addColumn('languages', 'text');
        $Table->addColumn('currency', 'string', ['length' => 3]);
        $Table->addColumn('active', 'smallint', ['default' => 1]);
        $Table->setPrimaryKey(['countries_id']);

        QUI::getSchemaManager()->createTable($Table);
    }
}
