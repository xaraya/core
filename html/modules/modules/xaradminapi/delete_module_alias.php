<?php
/**
 * File: $Id$
 *
 * Remove an alias for a module name
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * remove an alias for a module name
 * (only used for short URL support at the moment)
 *
 * @access public
 * @param aliasModName name of the 'fake' module you want to remove
 * @param modName name of the 'real' module it was assigned to
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM
 */
function modules_adminapi_delete_module_alias($args)
{
    extract($args);

    if (empty($aliasModName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'aliasModName');
        return;
    }

    $aliases = xarConfigGetVar('System.ModuleAliases');
    if (!isset($aliases[$aliasModName])) return false;
    // don't remove alias if it's already assigned to some other module !
    if ($aliases[$aliasModName] != $modName) return false;
    unset($aliases[$aliasModName]);
    xarConfigSetVar('System.ModuleAliases',$aliases);

    return true;
}

?>