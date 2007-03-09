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
    $xartable =& xarDBGetTables();

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    sys::import('modules.roles.class.xarQuery');
    $q = new xarQuery('SELECT');
    $q->addtable($xartable['roles'],'r');
    $q->addtable($xartable['rolemembers'], 'rm');
    $q->join('rm.id','r.id');
    $q->addfields(array('r.id AS uid','r.name AS name','r.users AS users','rm.parentid AS parentid'));

    $conditions = array();
    // Restriction by group.
    if (isset($group)) {
        $groups = explode(',', $group);
        foreach ($groups as $group) {
            $conditions[] = $q->eq('r.name',$group);
        }
    }
// Restriction by parent group.
    if (isset($parent)) {
        $groups = explode(',', $parent);
        foreach ($groups as $group) {
            $group = xarModAPIFunc(
                'roles', 'user', 'get',
                array(
                    (is_numeric($group) ? 'uid' : 'name') => trim($group),
                    'type' => ROLES_GROUPTYPE
                )
            );
            if (isset($group['uid']) && is_numeric($group['uid'])) {
                $conditions[] = $q->eq('rm.parentid',$group['uid']);
            }
        }
    }
// Restriction by ancestor group.
    if (isset($ancestor)) {
        $groups = explode(',', $ancestor);
        $q1 = new xarQuery('SELECT');
        $q1->addtable($xartable['roles'],'r');
        $q1->addtable($xartable['roles'],'r1');
        $q1->addtable($xartable['rolemembers'], 'rm');
        $q1->join('rm.id','r.id');
        $q1->join('rm.parentid','r1.id');
        $q1->addfields(array('r.name','rm.id','r1.name','rm.parentid'));
        $q1->eq('r.type',ROLES_GROUPTYPE);
        $q1->run();
        $allgroups = $q1->output();
        $descendants = array();
        foreach ($groups as $group) {
            $descendants = array_merge($descendants,_getDescendants($group,$allgroups));
        }
        $ids = array();
        foreach ($descendants as $descendant) {
            if (!in_array($descendant[1],$ids)) {
                $ids[] = $descendant[1];
                $conditions[] = $q->eq('rm.id',$descendant[1]);
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
        if($group['r1.name'] == $ancestor)
        $descendants[$group['rm.id']] = array($group['r.name'],$group['rm.id']);
    }
    foreach($descendants as $descendant){
        $subgroups = _getDescendants($descendant[0],$groups);
        foreach($subgroups as $subgroup){
            $descendants[$subgroup['rm.id']] = $subgroup['rm.id'];
        }
    }
    return $descendants ;
}
?>
