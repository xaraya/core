<?php
/**
 * Get list of modules calling a particular hook module
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Get list of modules calling a particular hook module
 *
 * @author Xaraya Development Team
 * @param array    $args array of optional parameters<br/>
 *        string   $args['hookModName'] hook module we're looking for<br/>
 *        string   $args['hookObject'] the object of the hook (item, module, ...) (optional)<br/>
 *        string   $args['hookAction'] the action on that object (transform, display, ...) (optional)<br/>
 *        string   $args['hookArea'] the area we're dealing with (GUI, API) (optional)
 * @return array modules calling this hook module
 * @throws BAD_PARAM
 */
function modules_adminapi_gethookedmodules(Array $args=array())
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