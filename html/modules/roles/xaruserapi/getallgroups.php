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
function roles_userapi_getallgroups()
{
    $dbconn =& xarDBGetConn(0);
    $xartable =& xarDBGetTables();

    $groupstable = $xartable['roles'];

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    $roles = new xarRoles();

    return $roles->getgroups();

}

?>