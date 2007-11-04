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
 * @throws BAD_PARAM
 */
function modules_adminapi_add_module_alias($args)
{
    extract($args);

    if (empty($modName)) throw new EmptyParameterException('modName');
    if (empty($aliasModName)) throw new EmptyParameterException('aliasModName');

    // Check if the module name we want to define is already in use
    if (xarMod_getBaseInfo($aliasModName)) {
        throw new DuplicateException(array('module alias',$aliasModName));
    } else {
        // We did not find the base info, that is good, no?
    }

    // Check if the alias we want to set it to *does* exist
    if (!xarMod_getBaseInfo($modName)) return;

    // Get the list of current aliases
    $aliases = xarConfigVars::Get(null, 'System.ModuleAliases');
    if (!isset($aliases)) {
        $aliases = array();
    }
    if (!empty($aliases[$aliasModName]) && $aliases[$aliasModName] != $modName) {
        throw new DuplicateException(array($aliasModName,$aliases[$aliasModName]),'Module alias #(1) is already used by module #(2)');
    }

    // the direction is fake module name -> true module, not the reverse !
    $aliases[$aliasModName] = $modName;
    xarConfigVars::Set(null, 'System.ModuleAliases', $aliases);

    return true;
}

?>
