<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Disable hooks between a caller module and a hook module
 * Note : generic hooks will not be disabled if a specific item type is given
 *
 * @author Xaraya Development Team
 * @param array    $args array of optional parameters<br/>
 *        string   $args['callerModName'] caller module<br/>
 *        string   $args['callerItemType'] optional item type for the caller module<br/>
 *        string   $args['hookModName'] hook module
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM
 */
function modules_adminapi_disablehooks(Array $args=array())
{
    // Security Check (called by other modules, so we can't use one this here)
    //    if(!xarSecurityCheck('ManageModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName)) throw new EmptyParameterException('callerModName');
    if (empty($hookModName))  throw new EmptyParameterException('hookModName');

    if (empty($callerItemType)) {
        $callerItemType = 0;
    }

    return xarHooks::detach($hookModName, $callerModName, $callerItemType);
}

?>
