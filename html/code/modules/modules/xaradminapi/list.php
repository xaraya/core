<?php
/**
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
 * Obtain list of modules (deprecated)
 *
 * @author Xaraya Development Team
 * @return array the known modules
 * @throws NO_PERMISSION
 */
function modules_adminapi_list(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Obtain information
    if (!isset($state)) $state = '';
    $modList = xarMod::apiFunc('modules',
                          'admin',
                          'getlist',
                          array('filter'     => array('State' => $state)));
    //throw back
    if (!isset($modList)) return;

    return $modList;
}

?>
