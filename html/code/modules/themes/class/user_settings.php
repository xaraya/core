<?php
/**
 * Themes User Settings
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
**/
sys::import('modules.dynamicdata.class.objects.base');

class ThemesUserSettings extends DataObject
{
    function updateItem(Array $data = array())
    {
        foreach ($this->properties as $name => $setting) {
            xarModUserVars::set('themes', $name, $setting->value);
        }
    }
}