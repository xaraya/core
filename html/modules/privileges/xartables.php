<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Table information for privileges module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Security Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 */
function privileges_xartables()
{
    // Initialise table array
    $xartable = array();

    $privileges = xarDBGetSiteTablePrefix() . '_privileges';
    $privmembers = xarDBGetSiteTablePrefix() . '_privmembers';
    $roles = xarDBGetSiteTablePrefix() . '_roles';
    $rolemembers = xarDBGetSiteTablePrefix() . '_rolemembers';
    $acl = xarDBGetSiteTablePrefix() . '_acl';
    $masks = xarDBGetSiteTablePrefix() . '_masks';
    $instances = xarDBGetSiteTablePrefix() . '_instances';

    // Set the table name
    $xartable['privileges'] = $privileges;
    $xartable['privmembers'] = $privmembers;
    $xartable['roles'] = $roles;
    $xartable['rolemembers'] = $rolemembers;
    $xartable['acl'] = $acl;
    $xartable['masks'] = $masks;
    $xartable['instances'] = $instances;

	// Return the table information
    return $xartable;
}

?>