<?php
/**
 * File: $Id$
 *
 * Generate all group listings
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * viewallgroups - generate all groups listing.
 * @param none
 * @return groups listing of available groups
 */
function roles_userapi_getallgroups($args)
{
    extract($args);
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    $q = new xarQuery('SELECT');
    $q->addtable($xartable['roles'],'r');
    $q->addtable($xartable['rolemembers'], 'rm');
    $q->join('rm.xar_uid','r.xar_uid');
    $q->addfields(array('r.xar_uid','r.xar_name','r.xar_users','rm.xar_parentid'));

    $conditions = array();
    // Restriction by group.
    if (isset($group)) {
        $groups = explode(',', $group);
        foreach ($groups as $group) {
            $conditions[] = $q->eq('r.xar_name',$group);
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
                    'type' => 1
                )
            );
            if (isset($group['uid']) && is_numeric($group['uid'])) {
                $conditions[] = $q->eq('rm.xar_parentid',$group['uid']);
            }
        }
    }
    if (count($conditions) != 0) $q->qor($conditions);
    $q->eq('r.xar_type',1);
    $q->run();

//this is a kludge, but xarQuery doesn't have this functionality yet
    $groups = array();
    foreach ($q->output() as $group) {
        $groups[] = array('uid' => $group['r.xar_uid'],
                          'name' => $group['r.xar_name'],
                          'users' => $group['r.xar_users'],
                          'parentid' => $group['rm.xar_parentid']);
    }

    return $groups;
}

?>