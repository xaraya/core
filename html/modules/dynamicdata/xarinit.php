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
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamic_data = $xartable['dynamic_data'];
    $dynamic_properties = $xartable['dynamic_properties'];

    include ('includes/xarTableDDL.php');

    $fields = array('xar_dd_id'       => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0',
                                              'increment'   => true,
                                              'primary_key' => true),
                    'xar_dd_propid'   => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
/* only needed if we go for freely extensible fields per item
                    'xar_dd_moduleid' => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
                    'xar_dd_itemtype' => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
*/
                    'xar_dd_itemid'   => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
                    'xar_dd_value'    => array('type'        => 'blob', // or text ?
                                              'size'        => 'medium',
                                              'null'        => 'false')
              );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $sql is empty
    $sql = xarDBCreateTable($dynamic_data,$fields);
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $sql = xarDBCreateIndex($dynamic_data,
                           array('name'   => 'i_xar_dd_propid',
                                 'fields' => array('xar_dd_propid')));
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $sql = xarDBCreateIndex($dynamic_data,
                           array('name'   => 'i_xar_dd_itemid',
                                 'fields' => array('xar_dd_itemid')));
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }


    $fields = array('xar_prop_id'         => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0',
                                                  'increment'   => true,
                                                  'primary_key' => true),
                    'xar_prop_moduleid'   => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                    'xar_prop_itemtype'   => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                    'xar_prop_label'      => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'null'        => false,
                                                  'default'     => ''),
                    'xar_prop_dtype'      => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => NULL),
                    'xar_prop_default'    => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'default'     => NULL),
                    'xar_prop_validation' => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'default'     => NULL)
              );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $sql is empty
    $sql = xarDBCreateTable($dynamic_properties,$fields);
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

// TODO: evaluate efficiency of combined index vs. individual ones
    $sql = xarDBCreateIndex($dynamic_properties,
                           array('name'   => 'i_xar_prop_combo',
                                 'fields' => array('xar_prop_moduleid',
                                                   'xar_prop_itemtype',
                                                   'xar_prop_label'),
                                 'unique' => 'true'));
    if (empty($sql)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    xarModSetVar('dynamicdata', 'bold', 0);
    xarModSetVar('dynamicdata', 'itemsperpage', 10);

    xarModSetVar('dynamicdata', 'SupportShortURLs', 0);

    xarBlockTypeRegister('dynamicdata', 'form');

    // when a new module item is being specified
    if (!xarModRegisterHook('item', 'new', 'GUI',
                           'dynamicdata', 'admin', 'newhook')) {
        return false;
    }
    // when a module item is created (uses 'dd_*')
    if (!xarModRegisterHook('item', 'create', 'API',
                           'dynamicdata', 'admin', 'createhook')) {
        return false;
    }
    // when a module item is being modified (uses 'dd_*')
    if (!xarModRegisterHook('item', 'modify', 'GUI',
                           'dynamicdata', 'admin', 'modifyhook')) {
        return false;
    }
    // when a module item is updated (uses 'dd_*')
    if (!xarModRegisterHook('item', 'update', 'API',
                           'dynamicdata', 'admin', 'updatehook')) {
        return false;
    }
    // when a module item is deleted
    if (!xarModRegisterHook('item', 'delete', 'API',
                           'dynamicdata', 'admin', 'deletehook')) {
        return false;
    }
    // when a module configuration is being modified (uses 'dd_*')
    if (!xarModRegisterHook('module', 'modifyconfig', 'GUI',
                           'dynamicdata', 'admin', 'modifyconfighook')) {
        return false;
    }
    // when a module configuration is updated (uses 'dd_*')
    if (!xarModRegisterHook('module', 'updateconfig', 'API',
                           'dynamicdata', 'admin', 'updateconfighook')) {
        return false;
    }
    // when a whole module is removed, e.g. via the modules admin screen
    // (set object ID to the module name !)
    if (!xarModRegisterHook('module', 'remove', 'API',
                           'dynamicdata', 'admin', 'removehook')) {
        return false;
    }

// TODO: replace this with block/cached variables/special template tag/... ?
//
//       Ideally, people should be able to use the dynamic fields in their
//       module templates as if they were 'normal' fields -> this means
//       adapting the get() function in the user API of the module, perhaps...

    // when a module item is being displayed
    if (!xarModRegisterHook('item', 'display', 'GUI',
                           'dynamicdata', 'user', 'displayhook')) {
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
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    include ('includes/xarTableDDL.php');

    // Generate the SQL to drop the table using the API
    $sql = xarDBDropTable($xartable['dynamic_data']);
    if (empty($sql)) return; // throw back

    // Drop the table
    $dbconn->Execute($sql);
    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Generate the SQL to drop the table using the API
    $sql = xarDBDropTable($xartable['dynamic_properties']);
    if (empty($sql)) return; // throw back

    // Drop the table
    $dbconn->Execute($sql);
    // Check for an error with the database code, and if so raise the
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Delete any module variables
    xarModDelVar('dynamicdata', 'itemsperpage');
    xarModDelVar('dynamicdata', 'bold');

    xarModDelVar('dynamicdata', 'SupportShortURLs');

    xarBlockTypeUnregister('dynamicdata', 'form');

    // Remove module hooks
    if (!xarModUnregisterHook('item', 'new', 'GUI',
                             'dynamicdata', 'admin', 'newhook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'create', 'API',
                             'dynamicdata', 'admin', 'createhook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'modify', 'GUI',
                             'dynamicdata', 'admin', 'modifyhook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'update', 'API',
                             'dynamicdata', 'admin', 'updatehook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('item', 'delete', 'API',
                             'dynamicdata', 'admin', 'deletehook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'modifyconfig', 'GUI',
                             'dynamicdata', 'admin', 'modifyconfighook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'updateconfig', 'API',
                             'dynamicdata', 'admin', 'updateconfighook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarModUnregisterHook('module', 'remove', 'API',
                             'dynamicdata', 'admin', 'removehook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }

// TODO: replace this with block/cached variables/special template tag/... ?
//
//       Ideally, people should be able to use the dynamic fields in their
//       module templates as if they were 'normal' fields -> this means
//       adapting the get() function in the user API of the module, perhaps...

    if (!xarModUnregisterHook('item', 'display', 'GUI',
                             'dynamicdata', 'user', 'displayhook')) {
        xarSessionSetVar('errormsg', xarML('Could not unregister hook'));
    }

    // Deletion successful
    return true;
}

?>
