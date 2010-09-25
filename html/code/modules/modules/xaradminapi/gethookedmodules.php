<?php
/**
 * Get list of modules calling a particular hook module
 * @package Xaraya eXtensible Management System
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Get list of modules calling a particular hook module
 *
 * @author Xaraya Development Team
 * @param $args['hookModName'] hook module we're looking for
 * These args are deprecated since they're irrelevent to this function,
 * it's all or nothing with hooks, you hook one object/action/area, you hook them all
 * @param $args['hookObject'] the object of the hook (item, module, ...) (optional)
 * @param $args['hookAction'] the action on that object (transform, display, ...) (optional)
 * @param $args['hookArea'] the area we're dealing with (GUI, API) (optional)
 * @returns array
 * @return array of modules calling this hook module
 * @throws BAD_PARAM
 */
function modules_adminapi_gethookedmodules($args)
{
// Security Check (called by other modules, so we can't use one this here)
//    if(!xarSecurityCheck('ManageModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($hookModName)) throw new EmptyParameterException('hookModName');
    
    return xarHooks::getObserverSubjects($hookModName);

}
?>