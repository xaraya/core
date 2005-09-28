<?php
/**
 * File: $Id$
 *
 * Generate all groups listing
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * getallgroups - generate all groups listing.
 * @param none
 * @return groups listing of available groups
 */
function roles_adminapi_getallgroups()
{
// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    $groups = xarModAPIFunc('roles','user','getallgroups');
    return $groups;
}


?>