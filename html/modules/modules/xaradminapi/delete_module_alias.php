<?php

/**
 * remove an alias for a module name
 * (only used for short URL support at the moment)
 *
 * @access public
 * @param aliasModName name of the 'fake' module you want to remove
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM
 */
function modules_adminapi_delete_module_alias($args)
{
    extract($args);

    if (empty($aliasModName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'aliasModName');
        return;
    }

    $aliases = xarConfigGetVar('System.ModuleAliases');
    if (!isset($aliases[$aliasModName])) return false;
    unset($aliases[$aliasModName]);
    xarConfigSetVar('System.ModuleAliases',$aliases);

    return true;
}

?>