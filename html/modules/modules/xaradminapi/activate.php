<?php

/**
 * Activate a module if it has an active function, otherwise just set the state to active
 *
 * @access public
 * @param regid module's registered id
 * @returns bool
 * @raise BAD_PARAM
 */
function modules_adminapi_activate($args)
{
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $modInfo = xarModGetInfo($regid);
    if (!isset($modInfo) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return NULL;
    }

    // Get module database info
    xarMod__loadDbInfo($modInfo['name'], $modInfo['osdirectory']);

    // Module activate function

    // pnAPI compatibility
    $xarinitfilename = 'modules/'. $modInfo['osdirectory'] .'/xarinit.php';
    if (!file_exists($xarinitfilename)) {
        $xarinitfilename = 'modules/'. $modInfo['osdirectory'] .'/pninit.php';
    }
    @include_once $xarinitfilename;

    $func = $modInfo['name'] . '_activate';
    if (function_exists($func)) {
        if ($func() != true) {
            return false;
        }
    }

    // Update state of module
    $res = xarModAPIFunc('modules',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_ACTIVE));
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return NULL;
    }

    return true;
}
?>