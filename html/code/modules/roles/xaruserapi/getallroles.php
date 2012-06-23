<?php
/**
 * Get all roles
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * get all roles
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        string   $args['order'] comma-separated list of order items; default 'name'<br/>
 *        string   $args['selection'] extra coonditions passed into the where-clause<br/>
 *        string   $args['include'] comma-separated list of role names<br/>
 *        string   $args['exclude'] comma-separated list of role names
 * @return mixed array of roles, or false on failure
 */
function roles_userapi_getallroles(Array $args=array())
{
    if(!xarSecurityCheck('ReadRoles')) {return;}
    extract($args);

    // Optional arguments.
    if (!isset($startnum)) $startnum = 1;
    if (!isset($numitems)) $numitems = (int)xarModVars::get('roles', 'items_per_page');

    sys::import('xaraya.structures.query');
    $q = new Query();
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
        $q->eq('r.itemtype',$itemtype);
    }

    // State
    if (!empty($state) && is_numeric($state) && $state != xarRoles::ROLES_STATE_CURRENT) {
        $q->eq('r.state',$state);
    } else {
        $q->ne('r.state',xarRoles::ROLES_STATE_DELETED);
    }

    $q->addfield('r.id AS id');
    $q->addfield('r.name AS name');
    $q->addfield('r.itemtype AS itemtype');
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
    if (isset($include)) {
        foreach (explode(',', $include) as $include_field) {
            if ($itemtype == xarRoles::ROLES_USERTYPE) {
                $q->ne('uname',xarMod::apiFunc('roles', 'user', 'get', array('uname' => $include_field)));
            } elseif ($itemtype == xarRoles::ROLES_GROUPTYPE) {
                $q->ne('name',xarMod::apiFunc('roles', 'user', 'get', array('name' => $include_field)));
                $includedgroups[] = $include_field;
            }
        }
    }

    // Exclusions
    $excludedgroups = array();
    if (isset($exclude)) {
        foreach (explode(',', $exclude) as $exclude_field) {
            if ($itemtype == xarRoles::ROLES_USERTYPE) {
                $q->ne('uname',xarMod::apiFunc('roles', 'user', 'get', array('uname' => $exclude_field)));
            } elseif ($itemtype == xarRoles::ROLES_GROUPTYPE) {
                $q->ne('name',xarMod::apiFunc('roles', 'user', 'get', array('name' => $exclude_field)));
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
    $items['dditems'] = xarMod::apiFunc('dynamicdata','user','getitems',array('moduleid' => 27, 'itemtype' => $itemtype, 'itemids' => $itemids,'getobject' => true));
/*    for ($i = 0, $max = count($items); $i < $max; $i++) {
        if (!isset($properties[$items[$i]['id']])) continue;
        $items[$i] = array_merge($items[$i],$properties[$items[$i]['id']]);
    }
*/    return $items;
}

?>