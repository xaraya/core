<?php
/**
 * Test a user or group's privileges against a mask
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * Create a user
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param string $args['uname'] username of the user
 * @param string $args['name'] real name of the user
 * @param string $args['email'] email address of the user
 * @param string $args['pass'] password of the user
 * @param string $args['date'] registration date
 * @param string $args['valcode'] validation code
 * @param int    $args['state'] state of the account
 * @param string $args['authmodule'] authentication module
 * @param int    $args['uid'] user id to be used (import only)
 * @param string $args['cryptpass'] encrypted password to be used (import only)
 * @param int    $args['itemtype'] item type to create
 * @returns int
 * @return user ID on success, false on failure
 */

function roles_adminapi_create($args)
{
    // Get arguments
    extract($args);

    $baseitemtype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
	$args['basetype'] = $baseitemtype;

    if ($baseitemtype == ROLES_USERTYPE) {
		if (!isset($uname)) throw new EmptyParameterException('uname');
		if (!isset($email)) throw new EmptyParameterException('email');
		if (!isset($name)) throw new EmptyParameterException('name');
		if (!isset($state)) throw new EmptyParameterException('state');
		if (!isset($pass)) throw new EmptyParameterException('pass');
		$args['cryptpass'] = md5($pass);
    } elseif ($baseitemtype == ROLES_GROUPTYPE) {
		if (!isset($name)) throw new EmptyParameterException('name');
    }
	$args['type'] = $itemtype;
	if (empty($authmodule)) {
        $modInfo = xarMod_GetBaseInfo('authsystem');
        $args['modId'] = $modInfo['systemid'];
	}

    $role = new xarRole($args);
    $role->add();
    $uid = $role->getID();

    // Let any hooks know that we have created a new user.
    $item['module'] = 'roles';
    $item['itemtype'] = $itemtype;
    $item['itemid'] = $uid;
    xarModCallHooks('item', 'create', $uid, $item);

    // Return the id of the newly created user to the calling process
    return $uid;
}

?>
