<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Obtain list of modules (deprecated)
 *
 * @author Xaraya Development Team
 * @param none
 * @returns array
 * @return array of known modules
 * @raise NO_PERMISSION
 */
function modules_adminapi_list($args)
{
    // Get arguments from argument array
    extract($args);

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Obtain information
    if (!isset($state)) $state = '';
    $modList = xarModAPIFunc('modules',
                          'admin',
                          'getlist',
                          array('filter'     => array('State' => $state)));
    //throw back
    if (!isset($modList)) return;

    return $modList;
}

?>
