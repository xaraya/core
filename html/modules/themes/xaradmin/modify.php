<?php

/**
 * Modify theme settings
//TODO: Make the phpdoc true :)
 *
 * This function queries the database for
 * the theme's information.
 *
 * @param id theme id
 * @returns array
 * @return an array of variables to pass to the template
 */
function themes_admin_modify()
{
    $regId = xarVarCleanFromInput('id');

    if (!isset($regId)) {
        $msg = xarML('No Theme ID is specified',
                    'themes');
        xarExceptionSet(XAR_USER_EXCEPTION,
                    'MISSING_DATA',
                     new DefaultUserException($msg));
        return;
    }

    $themeInfo = xarThemeGetInfo($regId);
    //throw back
    if (!isset($themeInfo)) return;

    $themeName = $themeInfo['name'];

// Security Check
	if (!xarSecurityCheck('AdminTheme',0,'All','$themeName::$regId')) return;

    $themevars = xarTheme_getVarsByTheme($themeName);

    $displayInfo = array();
    foreach($themeInfo as $k => $v){
        $displayInfo[] = array('name' => $k, 'value' => $v);
    }

    // End form
    $data['authid'] = xarSecGenAuthKey();
    $data['id'] = $regId;
    $data['name'] = $themeInfo['name'];
    $data['themeInfo'] = $displayInfo;
    $data['themevars'] = $themevars;
	$data['savebutton'] = xarML('Save Changes');

    return $data;
}

?>