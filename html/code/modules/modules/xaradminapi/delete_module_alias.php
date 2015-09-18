<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * remove an alias for a module name
 * (only used for short URL support at the moment)
 *
 * @author Xaraya Development Team
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['aliasModName'] name of the 'fake' module you want to remove<br/>
 *        string   $args['modName'] name of the 'real' module it was assigned to
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM
 */
function modules_adminapi_delete_module_alias(Array $args=array())
{
    extract($args);

    if (empty($aliasModName)) throw new EmptyParameterException('aliasModName');

    $aliases = xarConfigVars::get(null, 'System.ModuleAliases');
    if (!isset($aliases[$aliasModName])) return false;
    // don't remove alias if it's already assigned to some other module !
    if ($aliases[$aliasModName] != $modName) return false;
    unset($aliases[$aliasModName]);
    xarConfigVars::set(null, 'System.ModuleAliases',$aliases);

    return true;
}

?>
