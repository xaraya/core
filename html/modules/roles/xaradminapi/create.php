<?php
/**
 * Create a user
 *
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 */
/**
 * Create a user
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param string $args['uname'] username of the user
 * @param string $args['realname'] real name of the user
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

    if(!isset($itemtype)) {
        $msg = xarML('Wrong arguments to groups_adminapi_create.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    $invalid = array();
    $baseitemtype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
	$args['basetype'] = $baseitemtype;

    if ($baseitemtype == ROLES_USERTYPE) {
		if (!isset($uname)) {
			$invalid[] = 'uname';
		}
		if (!isset($email)) {
			$invalid[] = 'email';
		}
		if (!isset($name)) {
			$invalid[] = 'name';
		}
		if (!isset($state)) {
			$invalid[] = 'state';
		}
		if (!isset($pass)) {
			$invalid[] = 'pass';
		}
		$args['cryptpass'] = md5($pass);
    } elseif ($baseitemtype == ROLES_GROUPTYPE) {
		if (!isset($name)) {
			$invalid[] = 'name';
		}
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            join(', ', $invalid),
            'admin', 'create', 'roles');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

	$args['type'] = $itemtype;
	if (empty($authmodule)) {
		$args['auth_module'] = 'authsystem';
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
