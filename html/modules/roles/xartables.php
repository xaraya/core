<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: John Cox
// Purpose of file:  Table information for base module
// ----------------------------------------------------------------------

function roles_xartables()
{
    // Initialise table array
    $xartable = array();

    $roles = xarDBGetSiteTablePrefix() . '_roles';
    $rolemembers = xarDBGetSiteTablePrefix() . '_rolemembers';
    $privileges = xarDBGetSiteTablePrefix() . '_privileges';
    $privmembers = xarDBGetSiteTablePrefix() . '_privmembers';
    $acl = xarDBGetSiteTablePrefix() . '_acl';
    $masks = xarDBGetSiteTablePrefix() . '_masks';
    $instances = xarDBGetSiteTablePrefix() . '_instances';
    $user_status   = xarConfigGetVar('prefix') . '_user_status';

    // Set the table name
    $xartable['roles'] = $roles;
    $xartable['rolemembers'] = $rolemembers;
    $xartable['privileges'] = $privileges;
    $xartable['privmembers'] = $privmembers;
    $xartable['acl'] = $acl;
    $xartable['masks'] = $masks;
    $xartable['instances'] = $instances;
    $xartable['user_status'] = $user_status;

    // Return the table information
    return $xartable;
}

?>