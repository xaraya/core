<?php
/**
 * Get a specific user by any of his attributes
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
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

    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    // Get user
    $q = new xarQuery('SELECT',$rolestable);
    $q->addfields(array(
                  'xar_uid', // UID is a reserved word in Oracle (cannot be redefined)
                  'xar_uname AS uname',
                  'xar_name AS name',
                  'xar_type', // TYPE is a key word in several databases (avoid for the future)
                  'xar_email AS email',
                  'xar_pass AS pass',
                  'xar_date_reg AS date_reg',
                  'xar_valcode AS valcode',
                  'xar_state AS state'
                ));
    if (!empty($uid) && is_numeric($uid)) {
        $q->eq('xar_uid',(int)$uid);
    }
    if (!empty($name)) {
        $q->eq('xar_name',$name);
    }
    if (!empty($uname)) {
        $q->eq('xar_uname',$uname);
    }
    if (!empty($email)) {
        $q->eq('xar_email',$email);
    }
    if (!empty($state) && $state == ROLES_STATE_CURRENT) {
        $q->ne('xar_state',ROLES_STATE_DELETED);
    }
    elseif (!empty($state) && $state != ROLES_STATE_ALL) {
        $q->eq('xar_state',(int)$state);
    }
    $q->eq('xar_type',$type);
    if (!$q->run()) return;

    // Check for no rows found, and if so return
    $user = $q->row();
    if ($user == array()) return false;
    // uid and type are reserved/key words in Oracle et al.
    $user['uid'] = $user['xar_uid'];
    $user['type'] = $user['xar_type'];
    return $user;
}

?>
