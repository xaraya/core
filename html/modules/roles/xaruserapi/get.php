<?php
/**
 * Get a specific user by any of his attributes
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
 * get a specific user by any of his attributes
 * uname, uid and email are guaranteed to be unique,
 * otherwise the first hit will be returned
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] id of user to get
 * @param $args['uname'] user name of user to get
 * @param $args['name'] name of user to get
 * @param $args['email'] email of user to get
 * @returns array
 * @return user array, or false on failure
 */
function roles_userapi_get($args)
{
    // Get arguments from argument array
    extract($args);
    if (empty($uid) && empty($name) && empty($uname) && empty($email)) {
        throw new EmptyParameterException('uid or name or uname or email');
    } elseif (!empty($uid) && !is_numeric($uid)) {
        throw new VariableValidationException(array('uid',$uid,'numeric'));
    }
    if ((empty($itemid) && !empty($uid))) {
        $itemid = $uid;
    }

    $xartable = xarDB::getTables();
    $rolestable = $xartable['roles'];

    // Get user
    sys::import('modules.roles.class.xarQuery');
    $q = new xarQuery('SELECT',$rolestable);
    $q->addfields(array(
                  'id', // UID is a reserved word in Oracle (cannot be redefined)
                  'uname',
                  'name',
                  'type', // TYPE is a key word in several databases (avoid for the future)
                  'email',
                  'pass',
                  'date_reg',
                  'valcode',
                  'state'
                ));
    if (!empty($uid) && is_numeric($uid)) {
        $q->eq('id',(int)$uid);
    }
    if (!empty($name)) {
        $q->eq('name',$name);
    }
    if (!empty($uname)) {
        $q->eq('uname',$uname);
    }
    if (!empty($email)) {
        $q->eq('email',$email);
    }
    if (!empty($state) && $state == ROLES_STATE_CURRENT) {
        $q->ne('state',ROLES_STATE_DELETED);
    }
    elseif (!empty($state) && $state != ROLES_STATE_ALL) {
        $q->eq('state',(int)$state);
    }
    if (!empty($type)) {
        $q->eq('type',$type);
    }
    if (!$q->run()) return;

    // Check for no rows found, and if so return
    $user = $q->row();
    if ($user == array()) return false;
    // uid and type are reserved/key words in Oracle et al.
    $user['uid'] = $user['id'];
    $user['type'] = $user['type'];
    return $user;
}

?>
