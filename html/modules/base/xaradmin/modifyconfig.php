<?php

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

    $locales = xarMLSListSiteLocales();
    $i = 0; $j = 0;
    foreach($locales as $locale) {
        $data['locales'][] = $locale; $i++;
    }
    $data['localeslist'] = '';
    foreach($locales as $locale) {
        $data['localeslist'] .= $locale; 
        $j++;
        if ($j < $i) $data['localeslist'] .= ',';
    }

    $data['translationsBackend'] = xarConfigGetVar('Site.MLS.TranslationsBackend');
    
    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');

    return $data;
}

?>