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
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Get module information
    $modInfo = xarModGetInfo($regid);
    if (!isset($modInfo)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
                       return;
    }

    if (empty($modInfo['osdirectory']) || !is_dir('modules/'. $modInfo['osdirectory'])) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid - directory: $modInfo[osdirectory]) does not exist."));
        return;
    }

    // Get module database info
    xarModDBInfoLoad($modInfo['name'], $modInfo['directory']);
    
    //Checks module dependency
    if (!xarModAPIFunc('modules','admin','verifydependency',array('regid'=>$regid))) {
		//TODO: Add description of the dependencies
    	$msg = xarML('The dependencies to initialize the module "#(1)" were not met.', $modInfo['displayname']);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_DEPENDENCY', $msg);
		
        return;
    }

    // Include module initialisation file
    //FIXME: added module File not exist exception

    // pnAPI compatibility
    $xarinitfile = '';
    if (file_exists('modules/'. $modInfo['osdirectory'] .'/xarinit.php')) {
        $xarinitfile = 'modules/'. $modInfo['osdirectory'] .'/xarinit.php';
    } elseif (file_exists('modules/'. $modInfo['osdirectory'] .'/pninit.php')) {
        $xarinitfile = 'modules/'. $modInfo['osdirectory'] .'/pninit.php';
    }
    if (!empty($xarinitfile)) {
        include_once $xarinitfile;
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
    if (!empty($xarinitfile)) {
        // FIXME: perhaps we need a module function not exist except to be raised here?
        // Load module init function
        $func = $modInfo['name'] . '_init';
        if (function_exists($func)) {
            if ($func() != true) {
		    	$msg = xarML('Module initialisation failed because the function returned false');
		        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', $msg);
        		return;
            }
        } else {
            // file exists, but function not found. Exception!
	    	$msg = xarML('Module initialisation failed because your module did not include an init function');
	        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', $msg);
       		return;
        }
    }

    // Update state of module
    $set = xarModAPIFunc('modules',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));
      
//    die(var_dump($set));
    if (!isset($set)) {
    	$msg = xarML('Module state change failed');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', $msg);
   		return;
    }

    // Success
    return true;
}
?>
