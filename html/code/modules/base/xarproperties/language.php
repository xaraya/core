<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This property displays a dropdown containing the list of languages available on this site as per the base module backend
 */
class LanguageListProperty extends SelectProperty
{
    public $id         = 36;
    public $name       = 'language';
    public $desc       = 'Language List';
/**
     * Retrieve the list of options on demand
     * 
     * @param void N/A
     * @return array Array of options
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        
        $options = array();
        $list = xarMLSListSiteLocales();
        asort($list);

        foreach ($list as $locale) {
            $locale_data =& xarMLSLoadLocaleData($locale);
            $name = $locale_data['/language/display'] . " (" . $locale_data['/country/display'] . ")";
            $options[] = array('id'   => $locale,
                                     'name' => $name,
                                    );
        }
        return $options;
    }
}
?>