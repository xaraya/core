<?php
/**
 * File: $Id$
 *
 * Define a module name as an alias for some other module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * define a module name as an alias for some other module
 * (only used for short URL support at the moment)
 *
 * @access public
 * @param modName name of the 'real' module you want to assign it to
 * @param aliasModName name of the 'fake' module you want to define
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM
 */
function modules_adminapi_add_module_alias($args)
{
    extract($args);

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($aliasModName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'aliasModName');
        return;
    }

    // Check if the module name we want to define is already in use
    if (xarMod_getBaseInfo($aliasModName)) {
        $msg = xarML('Module name #(1) is already in use', $aliasModName);
        xarExceptionSet(XAR_USER_EXCEPTION, 'AlreadyInUse', new DefaultUserException($msg));
        return;
    } else {
        // TODO: test this someday...
        //if (xarExceptionId() != 'MODULE_NOT_EXIST') return; // throw back
        //xarExceptionFree();
    }

    // Check if the alias we want to set it to *does* exist
    if (!xarMod_getBaseInfo($modName)) return;

    // Get the list of current aliases
    $aliases = xarConfigGetVar('System.ModuleAliases');
    if (!isset($aliases)) {
        $aliases = array();
    }
    // the direction is fake module name -> true module, not the reverse !
    $aliases[$aliasModName] = $modName;
    xarConfigSetVar('System.ModuleAliases', $aliases);

    return true;
}

?>