<?php
/**
 *  Return the field names and correct values for joining on users table
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
 * return the field names and correct values for joining on users table
 * example : SELECT ..., $name, $email,...
 *           FROM ...
 *           LEFT JOIN $table
 *               ON $field = <name of userid field>
 *           WHERE ...
 *               AND $email LIKE '%xaraya.com'
 *               AND $where
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uids'] optional array of uids that we are selecting on
 * @returns array
 * @return array('table' => 'xar_roles',
 *               'field' => 'xar_roles.xar_uid',
 *               'where' => 'xar_roles.xar_uid IN (...)',
 *               'name'  => 'xar_roles.xar_name',
 *               ...
 *               'email'  => 'xar_roles.xar_email')
 */
function roles_userapi_leftjoin($args)
{
    // Get arguments from argument array
    extract($args);

    // Optional argument
    if (!isset($uids)) {
        $uids = array();
    }

    // Security check
    if (!xarSecurityCheck('ViewRoles',0)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }
// TODO: check this !
    foreach ($uids as $uid) {
        if (!xarSecurityCheck('ReadRole',0,'All',"All:All:$uid")) {
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
            return;
        }
    }

    // Table definition
    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    $leftjoin = array();

    // Specify LEFT JOIN ... ON ... [WHERE ...] parts
    $leftjoin['table'] = $rolestable;
    $leftjoin['field'] = $rolestable . '.xar_uid';
    if (count($uids) > 0) {
        $cleanuids = array();
        foreach ($uids as $uid) {
            $uid = intval($uid);
            if (!is_int($uid) || $uid < 1) continue;
            $cleanuids[] = $uid;
        }
        $alluids = join(', ', $cleanuids);
        $leftjoin['where'] = $rolestable . '.xar_uid IN (' .
                             $alluids . ')';
    } else {
        $leftjoin['where'] = '';
    }

    // Add available columns in the roles table
    // note : we forget about pass and auth_module for now :-)
    $columns = array('uid','uname','name','email');
    foreach ($columns as $column) {
        $leftjoin[$column] = $rolestable . '.xar_' . $column;
    }

    return $leftjoin;
}

?>
