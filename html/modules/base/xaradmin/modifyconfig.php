<?php
/**
 * File: $Id
 *
 * Moidfy site configuration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage base
 * @author John Robeson
 * @author Greg Allan
 */
/**
 * Modify site configuration
 *
 * @return array of template values
 */
function base_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;

    if (xarConfigGetVar('Site.Core.DefaultModuleType') == 'admin'){
    // Get list of user capable mods
        $data['mods'] = xarModAPIFunc('modules',
                          'admin',
                          'GetList',
                          array('filter'     => array('AdminCapable' => 1)));
        $mods = array();
        foreach($mods as $mod) {
            $data['mods'][] = array('displayname' => $mod);
        }
    } else {
        $data['mods'] = xarModAPIFunc('modules',
                          'admin',
                          'GetList',
                          array('filter'     => array('UserCapable' => 1)));
        $mods = array();
        foreach($mods as $mod) {
            $data['mods'][] = array('displayname' => $mod);
        }
    }

    $localehome = "var/locales";
    if (!file_exists($localehome)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'LOCALE_NOT_AVAILABLE', new SystemException('The locale directory was not found.'));
    }
    $dd = opendir($localehome);
    $locales = array();
    while ($filename = readdir($dd)) {
            if (is_dir($localehome . "/" . $filename) && file_exists($localehome . "/" . $filename . "/locale.xml")) {
                $locales[] = $filename;
            }
    }
    closedir($dd);

    $allowedlocales = xarConfigGetVar('Site.MLS.AllowedLocales');
    foreach($locales as $locale) {
        if (in_array($locale, $allowedlocales)) $active = true;
        else $active = false;
        $data['locales'][] = array('name' => $locale, 'active' => $active);
    }
    $data['translationsBackend'] = xarConfigGetVar('Site.MLS.TranslationsBackend');
    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');

    return $data;
}

?>
