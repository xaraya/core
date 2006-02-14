<?php
/**
 * Language List Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle the language list property
 *
 * @package dynamicdata
 */
class Dynamic_LanguageList_Property extends Dynamic_Select_Property
{
    public $id = 36;
    public $name = 'language';
    public $label = 'Language List';
    public $format = '36';

    function Dynamic_LanguageList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {

            $list = xarMLSListSiteLocales();

            asort($list);

            foreach ($list as $locale) {
                $locale_data =& xarMLSLoadLocaleData($locale);
                $name = $locale_data['/language/display'] . " (" . $locale_data['/country/display'] . ")";
                $this->options[] = array(
                    'id'   => $locale,
                    'name' => $name,
                );
            }
        }
    }
}

?>
