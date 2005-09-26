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
 * define a module name as an alias for some other module
 * (only used for short URL support at the moment)
 *
 * @author Xaraya Development Team
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($aliasModName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'aliasModName');
        return;
    }

    // Check if the module name we want to define is already in use
    if (xarMod_getBaseInfo($aliasModName)) {
        $msg = xarML('Module name #(1) is already in use', $aliasModName);
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
        return;
    } else {
        // TODO: test this someday...
        //if (xarCurrentErrorID() != 'MODULE_NOT_EXIST') return; // throw back
        //xarErrorFree();
    }

    // Check if the alias we want to set it to *does* exist
    if (!xarMod_getBaseInfo($modName)) return;

    // Get the list of current aliases
    $aliases = xarConfigGetVar('System.ModuleAliases');
    if (!isset($aliases)) {
        $aliases = array();
    }
    if (!empty($aliases[$aliasModName]) && $aliases[$aliasModName] != $modName) {
        $msg = xarML('Module alias #(1) is already used by module #(2)', $aliasModName, $aliases[$aliasModName]);
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
        return;
    }

    // the direction is fake module name -> true module, not the reverse !
    $aliases[$aliasModName] = $modName;
    xarConfigSetVar('System.ModuleAliases', $aliases);

    return true;
}

?>