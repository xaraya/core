<?php

/**
 * Obtain list of themes
 *
 * @param none
 * @returns array
 * @return array of known themes
 * @raise NO_PERMISSION
 */
function themes_adminapi_list()
{
// Security Check
	if(!xarSecurityCheck('AdminTheme')) return;

    // Obtain information
    $themeList = xarModAPIFunc('themes', 
                          'admin', 
                          'GetThemeList', 
                          array('filter'     => array('State' => XARTHEME_STATE_ANY)));
    //throw back
    if (!isset($themeList)) return;

    return $themeList;
}

?>