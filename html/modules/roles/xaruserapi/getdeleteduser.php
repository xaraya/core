<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * get a specific deleted user by any of his attributes
 *
 * uname, id and email are guaranteed to be unique,
 * otherwise the first hit will be returned
 * @author Richard Cave <rcave@xaraya.com>
 * @param $args['id'] id of user to get
 * @param $args['uname'] user name of user to get
 * @param $args['name'] name of user to get
 * @param $args['email'] email of user to get
 * @return array
 */
function roles_userapi_getdeleteduser($args)
{
    // Extract arguments
    extract($args);

    // Argument checks
    if (empty($id) && empty($name) && empty($uname) && empty($email)) {
        throw new EmptyParameterException('id or name or uname or email');
    } elseif (!empty($id) && !is_numeric($id)) {
        throw new VariableValidationException(array('id',$id,'numeric'));
    }

    // Set type to user
    if (empty($type)){
        $type = ROLES_USERTYPE;
    }

    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $rolestable = $xartable['roles'];

    $bindvars = array();
    $query = "SELECT id,
                   uname,
                   name,
                   email,
                   pass,
                   date_reg,
                   valcode,
                   state
            FROM $rolestable
            WHERE state = ? AND type = ?";
    $bindvars[] = 0;
    $bindvars[] = $type;

    if (!empty($id) && is_numeric($id)) {
        $query .= " AND id = ?";
        $bindvars[] = $id;
    } elseif (!empty($name)) {
        $query .= " AND name = ?";
        $bindvars[] = $name;
    } elseif (!empty($uname)) {
        // Need to add 'deleted' string to username
        $deleted = '[' . xarML('deleted') . ']';
        $query .= " AND uname LIKE ?";
        $bindvars[] = $uname.$deleted."%";
    } elseif (!empty($email)) {
        $query .= " AND email = ?";
        $bindvars[] = $email;
    }
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);


    if (!$result->first()) return false;

    list($id, $uname, $name, $email, $pass, $date, $valcode, $state) = $result->fields;
    $result->close();

    // Create the user array
    $user = array('id'         => $id,
                  'uname'       => $uname,
                  'name'        => $name,
                  'email'       => $email,
                  'pass'        => $pass,
                  'date_reg'    => $date,
                  'valcode'     => $valcode,
                  'state'       => $state);

    return $user;
}
?>
