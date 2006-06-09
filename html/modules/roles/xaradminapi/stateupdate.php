<?php
/**
 * Update a role state
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * Update a user's state
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
function roles_adminapi_stateupdate($args)
{
    extract($args);
    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($uid))   throw new EmptyParameterException('uid');
    if (!isset($state)) throw new EmptyParameterException('state');

    $item = xarModAPIFunc('roles',
                          'user',
                          'get',
                          array('uid' => $uid));

    if ($item == false) throw new IDNotFoundException($uid);

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolesTable = $xartable['roles'];

    $query = "UPDATE $rolesTable SET xar_state = ?" ;
    $bindvars = array($state);
    if (isset($valcode)) {
        $query .= ", xar_valcode = ?";
        $bindvars[] = $valcode;
    }
    $query .= " WHERE xar_uid = ?";
    $bindvars[] = $uid;

    $dbconn->Execute($query,$bindvars);

    return true;
}

?>
