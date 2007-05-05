<?php
/**
 * Recall deleted roles
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['id'] id of the role that is being called
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_recall($args)
{
    // Get arguments
    extract($args);

    if (!isset($id) || $id == 0) throw new EmptyParameterException('id');
    if (!isset($state) || $state == 0) throw new EmptyParameterException('state');

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    $deleted = '[' . xarML('deleted') . ']';

    $role = xarRoles::getRole($id);
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
