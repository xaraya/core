<?php
/**
 * Get all roles
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * get all roles
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['order'] comma-separated list of order items; default 'name'
 * @param $args['selection'] extra coonditions passed into the where-clause
 * @param $args['include'] comma-separated list of role names
 * @param $args['exclude'] comma-separated list of role names
 * @returns array
 * @return array of roles, or false on failure
 */
function roles_userapi_getallroles($args)
{
    if(!xarSecurityCheck('ReadRole')) {return;}
    extract($args);

    // Optional arguments.
    if (!isset($startnum)) $startnum = 1;
    if (!isset($numitems)) $numitems = xarModVars::get('roles', 'itemsperpage');

    sys::import('modules.roles.class.xarQuery');
    $q = new xarQuery();
    $xartable = xarDB::getTables();
    $q->addtable($xartable['roles'],'r');

    // Order
    if (!isset($order)) {
        $q->addorder('r.name');
    } else {
        foreach (explode(',', $order) as $order_field) {
            if (preg_match('/^[-]?(name|uname|email|id|state|date_reg)$/', $order_field)) {
                if (strstr($order_field, '-')) {
                    $q->addorder('r.' . $order_field,'DESC');
                } else {
                    $q->addorder('r.' . $order_field);
                }
            }
        }
    }

    // Itemtype
    if (!empty($itemtype)) {
        $q->eq('r.type',$itemtype);
    }

    // State
    if (!empty($state) && is_numeric($state) && $state != ROLES_STATE_CURRENT) {
        $q->eq('r.state',$state);
    } else {
        $q->ne('r.state',ROLES_STATE_DELETED);
    }

    $q->addfield('r.id AS id');
    $q->addfield('r.name AS name');
    $q->addfield('r.type AS type');
    $q->addfield('r.users AS users');
    $q->addfield('r.uname AS uname');
    $q->addfield('r.pass AS pass');
    $q->addfield('r.email AS email');
    $q->addfield('r.date_reg AS date_reg');
    $q->addfield('r.state AS state');
    $q->addfield('r.valcode AS valcode');
    $q->addfield('r.auth_module_id AS auth_module_id');

    // Inclusions
    $includedgroups = array();
    if (!isset($baseitemtype)) {
        $basetype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
    }
    if (isset($include)) {
        foreach (explode(',', $include) as $include_field) {
            if ($baseitemtype == ROLES_USERTYPE) {
                $q->ne('uname',xarModAPIFunc('roles', 'user', 'get', array('uname' => $include_field)));
            } elseif ($baseitemtype == ROLES_GROUPTYPE) {
                $q->ne('name',xarModAPIFunc('roles', 'user', 'get', array('name' => $include_field)));
                $includedgroups[] = $include_field;
            }
        }
    }

    // Exclusions
    $excludedgroups = array();
    if (!isset($baseitemtype)) {
        $basetype = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
    }
    if (isset($exclude)) {
        foreach (explode(',', $exclude) as $exclude_field) {
            if ($baseitemtype == ROLES_USERTYPE) {
                $q->ne('uname',xarModAPIFunc('roles', 'user', 'get', array('uname' => $exclude_field)));
            } elseif ($baseitemtype == ROLES_GROUPTYPE) {
                $q->ne('name',xarModAPIFunc('roles', 'user', 'get', array('name' => $exclude_field)));
                $excludedgroups[] = $exclude_field;
            }
        }
    }

    if ($includedgroups != array() || $excludedgroups != array()) {
        $q->addtable($xartable['rolemembers'],'rm');
        $q->join('r.id','rm.role_id');
        foreach ($includedgroups as $include) {
            $q->eq('rm.parent_id',$include);
        }
        foreach ($excludedgroups as $exclude) {
            $q->ne('rm.parent_id',$exclude);
        }
    }

// cfr. xarcachemanager - this approach might change later
    $expire = xarModVars::get('roles','cache.userapi.getallroles');
    if (!empty($expire)){
        $expire = unserialize($expire);
        $q = $expire;
    }

    if ($startnum == 0) {
        $q->setstartat($startnum);
        $q->setrowstodo($numitems);
    }
    if (!$q->run()) return;
    $items['nativeitems'] = $q->output();
    $itemids = array();
    foreach ($items['nativeitems'] as $item) $itemids[] = $item['id'];
    $items['dditems'] = xarModAPIFunc('dynamicdata','user','getitems',array('moduleid' => 27, 'itemtype' => $itemtype, 'itemids' => $itemids,'getobject' => true));
/*    for ($i = 0, $max = count($items); $i < $max; $i++) {
        if (!isset($properties[$items[$i]['id']])) continue;
        $items[$i] = array_merge($items[$i],$properties[$items[$i]['id']]);
    }
*/    return $items;
}

?>
