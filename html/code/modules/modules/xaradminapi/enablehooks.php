<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Enable hooks between a caller module and a hook module
 * Note : hooks will be enabled for all item types if no specific item type is given
 *
 * @author Xaraya Development Team
 * @param $args['callerModName'] caller module
 * @param $args['callerItemType'] optional item type for the caller module
 * @param $args['hookModName'] hook module
 * @returns bool
 * @return true if successfull
 * @throws BAD_PARAM
 */
function modules_adminapi_enablehooks($args)
{
    // Security Check (called by other modules, so we can't use one this here)
    //    if(!xarSecurityCheck('ManageModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName)) throw new EmptyParameterException('callerModName');
    if (empty($hookModName))   throw new EmptyParameterException('hookModName');

    // CHECKME: don't allow hooking to yourself !?
    if ($callerModName == $hookModName) {
        // <chris> this is allowed, for now (eg, roles usermenu > roles)        
        //throw new BadParameterException('hookModName');
    }

    if (empty($callerItemType)) {
        $callerItemType = 0;
    }

    return xarHooks::attach($hookModName, $callerModName, $callerItemType);
    
}

?>
