<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Table information for security module
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
function security_xartables()
{
    // Initialise table array
    $xartable = array();

    $permissions = xarDBGetSiteTablePrefix() . '_permissions';
    $permmembers = xarDBGetSiteTablePrefix() . '_permmembers';
    $participants = xarDBGetSiteTablePrefix() . '_participants';
    $partmembers = xarDBGetSiteTablePrefix() . 'partmembers';
    $acl = xarDBGetSiteTablePrefix() . '_acl';
    $schemas = xarDBGetSiteTablePrefix() . '_schemas';

    // Set the table name
    $xartable['permissions'] = $permissions;
    $xartable['permmembers'] = $permmembers;
    $xartable['participants'] = $participants;
    $xartable['partmembers'] = $partmembers;
    $xartable['acl'] = $acl;
    $xartable['schemas'] = $schemas;

	// Return the table information
    return $xartable;
}

?>