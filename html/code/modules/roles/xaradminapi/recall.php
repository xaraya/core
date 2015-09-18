<?php
/**
 * Recall deleted roles
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['id'] id of the role that is being called
 * @return boolean true on success, false on failure
 */
function roles_adminapi_recall(Array $args=array())
{
    // Get arguments
    extract($args);

    if (!isset($id) || $id == 0) throw new EmptyParameterException('id');
    if (!isset($state) || $state == 0) throw new EmptyParameterException('state');

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $rolestable = $xartable['roles'];

    $deleted = '[' . xarML('deleted') . ']';

    $role = xarRoles::get($id);
    $uname = explode($deleted,$role->getUser());
    $email = explode($deleted,$role->getEmail());
//            echo $uname[0];exit;
    $query = "UPDATE $rolestable
              SET uname = ?, email = ?, state = ?
              WHERE id = ?";
    $bindvars = array($uname[0],$email[0],$state,$id);
    $dbconn->Execute($query,$bindvars);

    // Let any hooks know that we have recalled this user.
    $item['module'] = 'roles';
    $item['itemid'] = $id;
    $item['method'] = 'recall';
    xarModCallHooks('item', 'create', $id, $item);

    //finished successfully
    return true;
}

?>
