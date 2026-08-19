<?php

namespace QUITests\QUI\Countries;

use QUI\Countries\Manager;

final class TestableManager extends Manager
{
    public static function parseRows(array $rows): array
    {
        return parent::parseCountryDbData($rows);
    }
}
