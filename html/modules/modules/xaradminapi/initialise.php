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
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Get module information
    $modInfo = xarModGetInfo($regid);
    if (!isset($modInfo)) throw new ModuleNotFoundException($regid,'Module (regid: $regid) does not exist.');

    //Checks module dependency
    if (!xarModAPIFunc('modules','admin','verifydependency',array('regid'=>$regid))) {
        //TODO: Add description of the dependencies
        $msg = xarML('The dependencies to initialize the module "#(1)" were not met.', $modInfo['displayname']);
        throw new Exception($msg);
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
        throw new Exception($msg);
    }

    // Success
    return true;
}
?>
