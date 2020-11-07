<?php
/**
 * (Module) Hooks handling subsystem - moved from modules to hooks for (future) clarity
 * @package core\hooks\legacy
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 */

/**
 * Carry out hook operations for module
 *
 * @package core\hooks
 * @uses xarModHooks::call()
 * @deprecated
 */
function xarModCallHooks($hookScope, $hookAction, $hookId, $extraInfo = NULL, $callerModName = NULL, $callerItemType = '')
{
    return xarModHooks::call($hookScope, $hookAction, $hookId, $extraInfo, $callerModName, $callerItemType);
}

/**
 * Get list of available hooks for a particular module[, scope] and action
 *
 * @package core\hooks
 * @uses xarModHooks::getList()
 * @deprecated
 */
function xarModGetHookList($callerModName, $hookScope, $hookAction, $callerItemType = '')
{
    return xarModHooks::getList($callerModName, $hookScope, $hookAction, $callerItemType);
}

/**
 * Check if a particular hook module is hooked to the current module (+ itemtype)
 *
 * @package core\hooks
 * @uses xarModHooks::isHooked()
 * @deprecated
 */
function xarModIsHooked($hookModName, $callerModName = NULL, $callerItemType = '')
{
    return xarModHooks::isHooked($hookModName, $callerModName, $callerItemType);
}

/**
 * register a hook function
 *
 * @package core\hooks
 * @uses xarModHooks::register()
 * @deprecated
 */
function xarModRegisterHook($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc)
{
    return xarModHooks::register($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc);
}

/**
 * unregister a hook function (deprecated - use unregisterHookModule or the standard deinstall for modules instead)
 *
 * @package core\hooks
 * @uses xarModHooks::unregister()
 * @deprecated
 */
function xarModUnregisterHook($hookScope, $hookAction, $hookArea,$hookModName, $hookModType, $hookModFunc)
{
    return xarModHooks::unregister($hookScope, $hookAction, $hookArea,$hookModName, $hookModType, $hookModFunc);
}

