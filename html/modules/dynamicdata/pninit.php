<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: mikespub
// Purpose of file:  Initialisation functions for dynamicdata
// ----------------------------------------------------------------------

/**
 * initialise the dynamicdata module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 */
function dynamicdata_init()
{
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dynamic_data = $pntable['dynamic_data'];
    $dynamic_properties = $pntable['dynamic_properties'];

    include ('includes/pnTableDDL.php');

    $fields = array('pn_dd_id'       => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0',
                                              'increment'   => true,
                                              'primary_key' => true),
                    'pn_dd_propid'   => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
/* only needed if we go for freely extensible fields per item
                    'pn_dd_moduleid' => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
                    'pn_dd_itemtype' => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
*/
                    'pn_dd_itemid'   => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
                    'pn_dd_value'    => array('type'        => 'blob', // or text ?
                                              'size'        => 'medium',
                                              'null'        => 'false')
              );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $sql is empty
    $sql = pnDBCreateTable($dynamic_data,$fields);
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $sql = pnDBCreateIndex($dynamic_data,
                           array('name'   => 'i_pn_dd_propid',
                                 'fields' => array('pn_dd_propid')));
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $sql = pnDBCreateIndex($dynamic_data,
                           array('name'   => 'i_pn_dd_itemid',
                                 'fields' => array('pn_dd_itemid')));
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }


    $fields = array('pn_prop_id'         => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0',
                                                  'increment'   => true,
                                                  'primary_key' => true),
                    'pn_prop_moduleid'   => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                    'pn_prop_itemtype'   => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                    'pn_prop_label'      => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'null'        => false,
                                                  'default'     => ''),
                    'pn_prop_dtype'      => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => NULL),
                    'pn_prop_default'    => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'default'     => NULL),
                    'pn_prop_validation' => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'default'     => NULL)
              );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $sql is empty
    $sql = pnDBCreateTable($dynamic_properties,$fields);
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

// TODO: evaluate efficiency of combined index vs. individual ones
    $sql = pnDBCreateIndex($dynamic_properties,
                           array('name'   => 'i_pn_prop_combo',
                                 'fields' => array('pn_prop_moduleid',
                                                   'pn_prop_itemtype',
                                                   'pn_prop_label'),
                                 'unique' => 'true'));
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    pnModSetVar('dynamicdata', 'bold', 0);
    pnModSetVar('dynamicdata', 'itemsperpage', 10);

    pnModSetVar('dynamicdata', 'SupportShortURLs', 0);

    pnBlockTypeRegister('dynamicdata', 'form');

    // when a new module item is being specified
    if (!pnModRegisterHook('item', 'new', 'GUI',
                           'dynamicdata', 'admin', 'newhook')) {
        return false;
    }
    // when a module item is created (uses 'dd_*')
    if (!pnModRegisterHook('item', 'create', 'API',
                           'dynamicdata', 'admin', 'createhook')) {
        return false;
    }
    // when a module item is being modified (uses 'dd_*')
    if (!pnModRegisterHook('item', 'modify', 'GUI',
                           'dynamicdata', 'admin', 'modifyhook')) {
        return false;
    }
    // when a module item is updated (uses 'dd_*')
    if (!pnModRegisterHook('item', 'update', 'API',
                           'dynamicdata', 'admin', 'updatehook')) {
        return false;
    }
    // when a module item is deleted
    if (!pnModRegisterHook('item', 'delete', 'API',
                           'dynamicdata', 'admin', 'deletehook')) {
        return false;
    }
    // when a module configuration is being modified (uses 'dd_*')
    if (!pnModRegisterHook('module', 'modifyconfig', 'GUI',
                           'dynamicdata', 'admin', 'modifyconfighook')) {
        return false;
    }
    // when a module configuration is updated (uses 'dd_*')
    if (!pnModRegisterHook('module', 'updateconfig', 'API',
                           'dynamicdata', 'admin', 'updateconfighook')) {
        return false;
    }
    // when a whole module is removed, e.g. via the modules admin screen
    // (set object ID to the module name !)
    if (!pnModRegisterHook('module', 'remove', 'API',
                           'dynamicdata', 'admin', 'removehook')) {
        return false;
    }

    // Initialisation successful
    return true;
}

/**
 * upgrade the dynamicdata module from an old version
 * This function can be called multiple times
 */
function dynamicdata_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch($oldversion) {
        case 1.0:
            // Code to upgrade from version 1.0 goes here
            break;
        case 2.0:
            // Code to upgrade from version 2.0 goes here
            break;
    }

    // Update successful
    return true;
}

/**
 * delete the dynamicdata module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 */
function dynamicdata_delete()
{
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    include ('includes/pnTableDDL.php');

    // Generate the SQL to drop the table using the API
    $sql = pnDBDropTable($pntable['dynamic_data']);
    if (empty($sql)) return; // throw back

    // Drop the table
    $dbconn->Execute($sql);
    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Generate the SQL to drop the table using the API
    $sql = pnDBDropTable($pntable['dynamic_properties']);
    if (empty($sql)) return; // throw back

    // Drop the table
    $dbconn->Execute($sql);
    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Delete any module variables
    pnModDelVar('dynamicdata', 'itemsperpage');
    pnModDelVar('dynamicdata', 'bold');

    pnModDelVar('dynamicdata', 'SupportShortURLs');

    pnBlockTypeUnregister('dynamicdata', 'form');

    // Remove module hooks
    if (!pnModUnregisterHook('item', 'new', 'GUI',
                             'dynamicdata', 'admin', 'newhook')) {
        pnSessionSetVar('errormsg', pnML('Could not unregister hook'));
    }
    if (!pnModUnregisterHook('item', 'create', 'API',
                             'dynamicdata', 'admin', 'createhook')) {
        pnSessionSetVar('errormsg', pnML('Could not unregister hook'));
    }
    if (!pnModUnregisterHook('item', 'modify', 'GUI',
                             'dynamicdata', 'admin', 'modifyhook')) {
        pnSessionSetVar('errormsg', pnML('Could not unregister hook'));
    }
    if (!pnModUnregisterHook('item', 'update', 'API',
                             'dynamicdata', 'admin', 'updatehook')) {
        pnSessionSetVar('errormsg', pnML('Could not unregister hook'));
    }
    if (!pnModUnregisterHook('item', 'delete', 'API',
                             'dynamicdata', 'admin', 'deletehook')) {
        pnSessionSetVar('errormsg', pnML('Could not unregister hook'));
    }
    if (!pnModUnregisterHook('module', 'modifyconfig', 'GUI',
                             'dynamicdata', 'admin', 'modifyconfighook')) {
        pnSessionSetVar('errormsg', pnML('Could not unregister hook'));
    }
    if (!pnModUnregisterHook('module', 'updateconfig', 'API',
                             'dynamicdata', 'admin', 'updateconfighook')) {
        pnSessionSetVar('errormsg', pnML('Could not unregister hook'));
    }
    if (!pnModUnregisterHook('module', 'remove', 'API',
                             'dynamicdata', 'admin', 'removehook')) {
        pnSessionSetVar('errormsg', pnML('Could not unregister hook'));
    }

    // Deletion successful
    return true;
}

?>
