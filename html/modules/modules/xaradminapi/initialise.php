<?php

/**
 * Initialise a module
 *
 * @param regid registered module id
 * @returns bool
 * @return
 * @raise BAD_PARAM, MODULE_NOT_EXIST
 */
function modules_adminapi_initialise($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
       $msg = xarML('Missing module regid (#(1)).', $regid);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));return;
    }

    // Get module information
    $modInfo = xarModGetInfo($regid);
    if (!isset($modInfo)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
                       return;
    }

    // Get module database info
    xarModDBInfoLoad($modInfo['name'], $modInfo['directory']);

    // Include module initialisation file
    //FIXME: added module File not exist exception

    // pnAPI compatibility
    if (file_exists('modules/'. $modInfo['osdirectory'] .'/xarinit.php')) {
        include_once 'modules/'. $modInfo['osdirectory'] .'/xarinit.php';
    } else  {
        @include_once 'modules/'. $modInfo['osdirectory'] .'/pninit.php';
    }

/*  FIXME xarUserGetLang appears to be legacy, do we need to enable the new locales?

    $langInitFile = 'modules/'. $modInfo['osdirectory'] .'/xarlang/' . xarVarPrepForOS(xarUserGetLang()) . '/init.php';
    // Include module language file for init functions if it exists
    if (file_exists($langInitFile)) {
    include_once $langInitFile;
    } else {
        // pnAPI compatibility
        $langInitFile = 'modules/'. $modInfo['osdirectory'] .'/pnlang/' . xarVarPrepForOS(xarUserGetLang()) . '/init.php';
        // Include module language file for init functions if it exists
        if (file_exists($langInitFile)) {
        include_once $langInitFile;
        }
    }
*/
    // FIXME: perhaps we need a module function not exist except to be raised here?
    // Load module init function
    $func = $modInfo['name'] . '_init';
    if (function_exists($func)) {
        if ($func() != true) {
            xarSessionSetVar('errormsg', xarML('Module initialisation failed because the function returned false'));
            return false;
        }

    } else {
        // file exists, but function not found. Exception!
        xarSessionSetVar('errormsg', xarML('Module initialisation failed because your module did not include an init function'));
        return false;
    }

    // Update state of module
    $set = xarModAPIFunc('modules',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));
    //die(var_dump($set));
    if (!isset($set)) {
        xarSessionSetVar('errormsg', xarML('Module state change failed'));
        return false;
    }

    // Success
    return true;
}
?>