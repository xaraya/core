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

    if (empty($modInfo['osdirectory']) || !is_dir('modules/'. $modInfo['osdirectory'])) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid - directory: $modInfo[osdirectory]) does not exist."));
        return;
    }

    // Get module database info
    xarMod__loadDbInfo($modInfo['name'], $modInfo['osdirectory']);

    // Module activate function

    // pnAPI compatibility
    $xarinitfile = '';
    if (file_exists('modules/'. $modInfo['osdirectory'] .'/xarinit.php')) {
        $xarinitfile = 'modules/'. $modInfo['osdirectory'] .'/xarinit.php';
    } elseif (file_exists('modules/'. $modInfo['osdirectory'] .'/pninit.php')) {
        $xarinitfile = 'modules/'. $modInfo['osdirectory'] .'/pninit.php';
    }
    if (!empty($xarinitfile)) {
        include_once $xarinitfile;

        $func = $modInfo['name'] . '_activate';
        if (function_exists($func)) {
            if ($func() != true) {
                return false;
            }
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
