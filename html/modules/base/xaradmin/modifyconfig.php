<?php

/**
 * Modify site configuration
 *
 * @return array of template values
 */
function base_admin_modifyconfig()
{
    // TODO -- Many more global configs to go in.
    // TODO -- Auth System
    // TODO -- ML.

    // Clear Session Vars
    xarSessionDelVar('base_statusmsg');

    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;

    if (xarConfigGetVar('Site.Core.DefaultModuleType') == 'admin'){
    // Get list of user capable mods
        $data['mods'] = xarModGetList(array('AdminCapable' => 1));
        $mods = array();
        foreach($mods as $mod) {
            $data['mods'][] = array('displayname' => $mod);
        }
    } else {
        $data['mods'] = xarModGetList(array('UserCapable' => 1));
        $mods = array();
        foreach($mods as $mod) {
            $data['mods'][] = array('displayname' => $mod);
        }
    }

    $locales = xarMLSListSiteLocales();
    foreach($locales as $locale) {
        $data['locales'][] = $locale;
    }


    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');

    return $data;
}

?>