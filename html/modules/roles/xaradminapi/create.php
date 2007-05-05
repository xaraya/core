<?php
/**
 * Test a user or group's privileges against a mask
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
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

    $baseitemtype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
    $args['basetype'] = $baseitemtype;

    if ($baseitemtype == ROLES_USERTYPE) {
        if (!isset($uname)) throw new EmptyParameterException('uname');
        if (!isset($email)) throw new EmptyParameterException('email');
        if (!isset($realname) && !isset($name)) throw new EmptyParameterException('realname');
        if (!isset($state)) throw new EmptyParameterException('state');
        if (!isset($pass)) throw new EmptyParameterException('pass');
        $args['cryptpass'] = md5($pass);
    } elseif ($baseitemtype == ROLES_GROUPTYPE) {
        if (!isset($realname) && !isset($name)) throw new EmptyParameterException('realname or name');
    }
    $args['name'] = isset($realname) ? $realname : $name;
    $args['type'] = $itemtype;
    if (empty($authmodule)) {
        $args['modId'] = xarMod::getID('authsystem');
    }

    sys::import('modules.dynamicdata.class.objects.master');
    $role = DataObjectMaster::getObject(array('module' => 'roles', 'itemtype' => $itemtype));
    $role->checkInput();
    $role->update();

    // Let any hooks know that we have created a new user.
    $item['module'] = 'roles';
    $item['itemtype'] = $itemtype;
    $item['itemid'] = $uid;
    xarModCallHooks('item', 'create', $uid, $item);

    // Return the id of the newly created user to the calling process
    return $uid;
}

?>
