<?php
/**
 * Language List Property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
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
    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 36;
        $info->name = 'language';
        $info->desc = 'Language List';
		$info->filepath   = 'modules/base/xarproperties';

        return $info;
    }

    function getOptions()
    {
        $list = xarMLSListSiteLocales();

        asort($list);

        foreach ($list as $locale) {
            $locale_data =& xarMLSLoadLocaleData($locale);
            $name = $locale_data['/language/display'] . " (" . $locale_data['/country/display'] . ")";
            $this->options[] = array('id'   => $locale,
                                     'name' => $name,
                                    );
        }
        return $this->options;
    }
}
?>
