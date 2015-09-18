<?php
/**
 * Update a role state
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * Update a user's state
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['id'] user ID<br/>
 *        string   $args['name'] user real name<br/>
 *        string   $args['uname'] user nick name<br/>
 *        string   $args['email'] user email address<br/>
 *        string   $args['pass'] user password
 * TODO: move url to dynamic user data
 *       replace with status
 * @param $args['url'] user url
 */
function roles_adminapi_stateupdate(Array $args=array())
{
    extract($args);
    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($id))   throw new EmptyParameterException('id');
    if (!isset($state)) throw new EmptyParameterException('state');

    $item = xarMod::apiFunc('roles',
                          'user',
                          'get',
                          array('id' => $id));

    if ($item == false) throw new IDNotFoundException($id);

    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

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
