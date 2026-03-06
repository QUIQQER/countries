<?php

/**
 * This file contains \QUI\Countries\Controls\Select
 */

namespace QUI\Countries\Controls;

use QUI;
use QUI\Countries\Country;
use QUI\Countries\Manager;

use function mb_strtolower;

/**
 * Country Select field
 */
class Select extends QUI\Control
{
    /**
     * constructor
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        // default
        $this->setAttributes([
            'name' => 'countries',
            'selected' => '',
            'class' => false,    // CSS class to add to the select HTML element
            'required' => false,
            'use-geo-location' => true
        ]);

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @return String
     * @see \QUI\Control::create()
     *
     */
    public function create(): string
    {
        $countries = Manager::getSortedList();
        $result = '<select data-qui="package/quiqqer/countries/bin/controls/Select" ';

        if ($this->getAttribute('name')) {
            $result .= ' name="' . $this->getAttribute('name') . '"';
        }

        if ($this->getAttribute('class')) {
            $result .= ' class="' . $this->getAttribute('class') . '"';
        }

        if ($this->getAttribute('required')) {
            $result .= ' required';
        }

        if (!$this->getAttribute('no-autocomplete')) {
            $result .= ' autocomplete="country-name"';
        } else {
            $result .= ' autocomplete="off"';
        }

        $result .= '>';

        $selected = $this->getAttribute('selected');

        if (empty($selected) && $this->getAttribute('use-geo-location')) {
            $Country = null;

            if (isset($_SERVER["GEOIP_COUNTRY_CODE"])) { // only for apache
                try {
                    $Country = QUI\Countries\Manager::get($_SERVER["GEOIP_COUNTRY_CODE"]);
                } catch (QUI\Exception) {
                }
            }

            if (!$Country) {
                try {
                    $Country = Manager::getDefaultCountry();
                } catch (QUI\Exception) {
                }
            }

            if ($Country) {
                $selected = $Country->getCode();
            }
        }

        /* @var $Country Country */
        foreach ($countries as $Country) {
            $result .= '<option value="' . $Country->getCode() . '"';

            if ($Country->getCodeToLower() == mb_strtolower($selected)) {
                $result .= ' selected="selected"';
            }

            $result .= '>';
            $result .= $Country->getName();
            $result .= '</option>';
        }

        $result .= '</select>';

        return $result;
    }
}
