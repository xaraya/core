<?php
/**
 * @package modules
 * @subpackage modules module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * 
 */
/**
 * Obtain list of modules (deprecated)
 *
 * @author Xaraya Development Team
 * @param none
 * @returns array
 * @return array of known modules
 * @throws NO_PERMISSION
 */
function modules_adminapi_list($args)
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
