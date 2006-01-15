<?php
/**
 * Update a role core info
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * Update a user's core info
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] user ID
 * @param $args['name'] user real name
 * @param $args['uname'] user nick name
 * @param $args['email'] user email address
 * @param $args['pass'] user password
 * TODO: move url to dynamic user data
 *       replace with status
 * @param $args['url'] user url
 */
function roles_adminapi_update($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($uid))   throw new EmptyParameterException('uid');
    if (!isset($name))  throw new EmptyParameterException('name');
    if (!isset($uname)) throw new EmptyParameterException('uname');
    if (!isset($email)) throw new EmptyParameterException('email');
    if (!isset($state)) throw new EmptyParameterException('state');

    $item = xarModAPIFunc('roles',
            'user',
            'get',
            array('uid' => $uid));

    if ($item == false) throw new IDNotFoundException($uid);

    if (empty($valcode)) {
        $valcode = '';
    }
    if (empty($home)) {
        $home = '';
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolesTable = $xartable['roles'];

    if (!empty($pass)){
        $cryptpass=md5($pass);
        $query = "UPDATE $rolesTable
                  SET xar_name = ?, xar_uname = ?, xar_email = ?,
                      xar_pass = ?, xar_valcode = ?, xar_state = ?
                WHERE xar_uid = ?";
        $bindvars = array($name,$uname,$email,$home,$cryptpass,$valcode,$state,$uid);
    } else {
        $query = "UPDATE $rolesTable
                SET xar_name = ?, xar_uname = ?, xar_email = ?,
                    xar_valcode = ?, xar_state = ?
                WHERE xar_uid = ?";
        $bindvars = array($name,$uname,$email,$valcode,$state,$uid);
    }
	xarModSetUserVar('roles','userhome',$home,$uid);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['name'] = $name;
    $item['home'] = $home;
    $item['uname'] = $uname;
    $item['email'] = $email;
    xarModCallHooks('item', 'update', $uid, $item);

    return true;
}

?>
