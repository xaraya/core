<?php
/**
 *  Return the field names and correct values for joining on users table
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
 * @param array    $args array of optional parameters<br/>
 *        array    $args['ids'] optional array of ids that we are selecting on
 * @return array array('table' => 'xar_roles',
 *               'field' => 'xar_roles.id',
 *               'where' => 'xar_roles.id IN (...)',
 *               'name'  => 'xar_roles.name',
 *               ...
 *               'email'  => 'xar_roles.email')
 */
function roles_userapi_leftjoin(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Optional argument
    if (!isset($ids)) {
        $ids = array();
    }

    // Security check
    if (!xarSecurityCheck('ViewRoles',0)) return;

// TODO: check this !
    foreach ($ids as $id) {
        if (!xarSecurityCheck('ReadRoles',0,'All',"All:All:$id")) return;
    }

    // Table definition
    $xartable = xarDB::getTables();
    $rolestable = $xartable['roles'];

    $leftjoin = array();

    // Specify LEFT JOIN ... ON ... [WHERE ...] parts
    $leftjoin['table'] = $rolestable;
    $leftjoin['field'] = $rolestable . '.id';
    if (count($ids) > 0) {
        $cleanids = array();
        foreach ($ids as $id) {
            $id = intval($id);
            if (!is_int($id) || $id < 1) continue;
            $cleanids[] = $id;
        }
        $allids = join(', ', $cleanids);
        $leftjoin['where'] = $rolestable . '.id IN (' .
                             $allids . ')';
    } else {
        $leftjoin['where'] = '';
    }

    // Add available columns in the roles table
    // note : we forget about pass and auth module for now :-)
    $columns = array('id','uname','name','email');
    foreach ($columns as $column) {
        $leftjoin[$column] = $rolestable . '.' . $column;
    }

    return $leftjoin;
}

?>