<?php
/**
 * Update a role state
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
 * Update a user's state
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['id'] user ID
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
    if (!isset($id))   throw new EmptyParameterException('id');
    if (!isset($state)) throw new EmptyParameterException('state');

    $item = xarModAPIFunc('roles',
                          'user',
                          'get',
                          array('id' => $id));

    if ($item == false) throw new IDNotFoundException($id);

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $rolesTable = $xartable['roles'];

    $query = "UPDATE $rolesTable SET state = ?" ;
    $bindvars = array($state);
    if (isset($valcode)) {
        $query .= ", valcode = ?";
        $bindvars[] = $valcode;
    }
    $query .= " WHERE id = ?";
    $bindvars[] = $id;

    $dbconn->Execute($query,$bindvars);

    return true;
}

?>
