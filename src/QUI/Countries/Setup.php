<?php

/**
 * This file contains the \QUI\Countries\Setup
 */

namespace QUI\Countries;

use QUI;

use QUI\Exception;

use function explode;
use function file_get_contents;
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
        $dataMd5 = $Config->getValue('general', 'dataMd5');

        // Countries
        $path = str_replace('src/QUI/Countries/Setup.php', '', __FILE__);
        $file = $path . '/db/intl.json';
        $fileMd5 = md5_file($file);

        $Table = QUI::getDataBase()->table();
        $Table->addColumn(Manager::getDataBaseTableName(), [
            'active' => 'int(1) NOT NULL DEFAULT 1'
        ]);

        if ($fileMd5 == $dataMd5) {
            return;
        }

        $data = json_decode(file_get_contents($path . '/db/intl.json'), true);
        $table = Manager::getDataBaseTableName();

        $Table = QUI::getDataBase()->table();
        $Table->delete($table);

        $Table->addColumn($table, [
            'countries_id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'countries_iso_code_2' => 'char(2) NOT NULL',
            'countries_iso_code_3' => 'char(3) NOT NULL',
            'numeric_code' => 'char(4) NOT NULL',
            'language' => 'char(3) NOT NULL',
            'languages' => 'text NOT NULL',
            'currency' => 'char(3) NOT NULL',
            'active' => 'int(1) NOT NULL DEFAULT 1'
        ]);

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
                QUI::getDataBase()->insert($table, [
                    'countries_iso_code_2' => $country,
                    'countries_iso_code_3' => $entry['three_letter_code'],
                    'numeric_code' => $entry['numeric_code'],
                    'language' => $language,
                    'languages' => json_encode($entry['languages']),
                    'currency' => $entry['currency_code']
                ]);
            } catch (QUI\Database\Exception $Exception) {
                QUI\System\Log::addWarning($Exception->getMessage());
            }
        }

        $Config->setValue('general', 'dataMd5', $fileMd5);
        $Config->save();
    }
}
