<?php
/**
 * Deactivate a module 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Deactivate a module if it has a deactive function, otherwise just set the state to deactive
 *
 * @author Xaraya Development Team
 * @access public
 * @param regid module's registered id
 * @returns bool
 * @raise BAD_PARAM
 */
function modules_adminapi_deactivate ($args)
{
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $modInfo = xarModGetInfo($regid);

    //Shouldnt we check first if the module is alredy ACTIVATED????
    //What should we do with UPGRADED STATE? What is it meant to?
    //  if ($modInfo['state'] != XARMOD_STATE_ACTIVE)

    // Module activate function
    // only run if the module is actually there. It may have been removed
    if ($modInfo['state'] != XARMOD_STATE_MISSING_FROM_ACTIVE) {
        if (!xarModAPIFunc('modules','admin','executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'deactivate'))) {
            //Raise an Exception
            return;
        }
    }
    // Update state of module
    $res = xarModAPIFunc('modules','admin','setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));



    if (function_exists('xarOutputFlushCached')) {
        xarOutputFlushCached('modules');
        xarOutputFlushCached('base-block');
    }

    return true;
}
?>
