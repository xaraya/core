<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');
/**
 * Handle the language list property
 */
class LanguageListProperty extends SelectProperty
{
    public $id         = 36;
    public $name       = 'language';
    public $desc       = 'Language List';

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
