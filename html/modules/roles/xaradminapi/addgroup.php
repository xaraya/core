<?php
/**
 * Add a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/**
 * addGroup - add a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['gname'] group name to add
 * @return true on success, false if group exists
 */
function roles_adminapi_addgroup($args)
{
    extract($args);

    if(!isset($gname)) throw new EmptyParameterException('gname');

    // Security Check
    if(!xarSecurityCheck('AddRole')) return;

	$new = array(
				'uname' => $gname,
				'itemtype' =< ROLES_GROUPTYPE
				);
    return xarModAPIFunc('roles','admin','create',$new);
}

?>
