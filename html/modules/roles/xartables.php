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
    $xartable['users_column'] = array(
        'uid'            => $users . '.xar_uid',
        'name'           => $users . '.xar_name',
        'uname'          => $users . '.xar_uname',
        'email'          => $users . '.xar_email',
        'pass'           => $users . '.xar_pass',
        'url'            => $users . '.xar_url',
        'date_reg'       => $users . '.xar_date_reg',
        'valcode'        => $users . '.xar_valcode',
        'state'          => $users . '.xar_state',
        'auth_module'    => $users . '.xar_auth_module'
     );

    // Get the name for the user data table
    $user_data  = xarConfigGetVar('prefix') . '_user_data';

    // Set the table name
    $xartable['user_data'] = $user_data;

    // Set the column names
    $xartable['user_data_column'] = array(
        'uda_id'          => $user_data . '.xar_uda_id',
        'uda_propid'      => $user_data . '.xar_uda_propid',
        'uda_uid'         => $user_data . '.xar_uda_uid',
        'uda_value'       => $user_data . '.xar_uda_value'
    );

    // Get the name for the user property table
    $user_property  = xarConfigGetVar('prefix') . '_user_property';

    // Set the table name
    $xartable['user_property'] = $user_property;

    // Set the column names
    $xartable['user_property_column'] = array(
        'prop_id'          => $user_property . '.xar_prop_id',
        'prop_label'       => $user_property . '.xar_prop_label',
        'prop_dtype'       => $user_property . '.xar_prop_dtype',
        'prop_default'     => $user_property . '.xar_prop_default',
        'prop_validation'  => $user_property . '.xar_prop_validation'
    );

    // Get the name for the autolinks item table
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

