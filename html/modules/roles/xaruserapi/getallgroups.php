<?php
/**
 * Generate all groups listing.
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
 * viewallgroups - generate all groups listing.
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param none
 * @return groups listing of available groups
 */

function roles_userapi_getallgroups($args)
{
    extract($args);
    $xartable = xarDB::getTables();

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    sys::import('modules.roles.class.xarQuery');
    $q = new xarQuery('SELECT');
    $q->addtable($xartable['roles'],'r');
    $q->addtable($xartable['rolemembers'], 'rm');
    $q->leftjoin('r.id','rm.role_id');
    $q->addfields(array('r.id AS id','r.name AS name','r.users AS users','rm.parent_id AS parentid'));

    $conditions = array();
    // Restriction by group.
    if (isset($group)) {
        $groups = explode(',', $group);
        foreach ($groups as $group) {
            $conditions[] = $q->peq('r.name',trim($group));
        }
    }
// Restriction by parent group.
    if (isset($parent)) {
        $groups = explode(',', $parent);
        foreach ($groups as $group) {
            $group = xarModAPIFunc(
                'roles', 'user', 'get',
                array(
                    (is_numeric($group) ? 'id' : 'name') => trim($group),
                    'type' => ROLES_GROUPTYPE
                )
            );
            if (isset($group['id']) && is_numeric($group['id'])) {
                $conditions[] = $q->peq('rm.parent_id',$group['id']);
            }
        }
    }
// Restriction by ancestor group.
    // FIXME: this is really broken
    if (isset($ancestor) && 0 == 1) {
        $q1 = new xarQuery('SELECT',$xartable['roles']);
        $q1->addfields(array('id','name'));
        $q1->eq('type',ROLES_GROUPTYPE);
        $q1->run();
        $allgroups = $q1->output();
        $descendants = array();
        $groups = explode(',', $ancestor);
        foreach ($groups as $group) {
            $descendants = array_merge($descendants,_getDescendants(trim($group),$allgroups));
        }
        $ids = array();
        foreach ($descendants as $descendant) {
            if (!in_array($descendant[1],$ids)) {
                $ids[] = $descendant[1];
                $conditions[] = $q->peq('rm.role_id',$descendant[1]);
            }
        }
    }

    if (count($conditions) != 0) $q->qor($conditions);
    $q->eq('r.type',ROLES_GROUPTYPE);
    $q->ne('r.state',ROLES_STATE_DELETED);
    $q->run();
    return $q->output();
}

function _getDescendants($ancestor,$groups)
{
    $descendants = array();
    foreach($groups as $group){
        if($group['name'] == $ancestor)
            $descendants[$group['id']] = array($group['name'],$group['id']);
    }
    foreach($descendants as $descendant){
        $subgroups = _getDescendants($descendant[0],$groups);
        foreach($subgroups as $subgroup){
            $descendants[$subgroup['id']] = $subgroup['id'];
        }
    }
    return $descendants ;
}
?>
