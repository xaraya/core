<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Initialise a module
 *
 * @author Xaraya Development Team
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Get module information
    $modInfo = xarModGetInfo($regid);
    if (!isset($modInfo)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
                       return;
    }

    //Checks module dependency
    if (!xarModAPIFunc('modules','admin','verifydependency',array('regid'=>$regid))) {
        //TODO: Add description of the dependencies
        $msg = xarML('The dependencies to initialize the module "#(1)" were not met.', $modInfo['displayname']);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_DEPENDENCY', $msg);

        return;
    }

    // Module deletion function
    if (!xarModAPIFunc('modules',
                       'admin',
                       'executeinitfunction',
                       array('regid'    => $regid,
                             'function' => 'init'))) {
        //Raise an Exception
        return;
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', $msg);
           return;
    }

    // Success
    return true;
}
?>