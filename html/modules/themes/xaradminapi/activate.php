<?php

/**
 * Activate a theme if it has an active function, otherwise just set the state to active
 *
 * @access public
 * @param regid theme's registered id
 * @returns bool
 * @raise BAD_PARAM
 */
function themes_adminapi_activate($args)
{
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $themeInfo = xarThemeGetInfo($regid);
    if (!isset($themeInfo) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return NULL;
    }


    // Update state of theme
    $res = xarModAPIFunc('themes',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARTHEME_STATE_ACTIVE));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return NULL;
    }

    return true;
}
?>