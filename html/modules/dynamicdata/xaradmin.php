<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: mikespub
// Purpose of file:  dynamic data administration display functions
// ----------------------------------------------------------------------

/**
 * This is a standard function to modify the configuration parameters of the
 * module
 */
function dynamicdata_admin_modifyconfig()
{
    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_admin_menu();

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'DynamicData::', '::', ACCESS_ADMIN)) {
        $msg = pnML('Not authorized to modify #(1) configuration settings',
                               'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    list($modid,
         $itemtype) = pnVarCleanFromInput('modid',
                                          'itemtype');
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'modifyconfig', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }
    $data['modid'] = $modid;
    if (empty($itemtype)) {
        $data['itemtype'] = '';
        $itemtype = null;
    } else {
        $data['itemtype'] = $itemtype;
    }

    // Generate a one-time authorisation code for this operation
    $data['authid'] = pnSecGenAuthKey();

    $modinfo = pnModGetInfo($modid);
    $data['module'] = pnML('for module "#(1)"', $modinfo['displayname']);
    if (!empty($itemtype)) {
        $data['module'] .= ' ' . pnML('type #(1)', $itemtype);
    }

    $data['newlink'] = pnModURL('dynamicdata','admin','newprop',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype));
    $data['formlink'] = pnModURL('dynamicdata','admin','viewform',
                                 array('modid' => $modid,
                                      'itemtype' => $itemtype));

    if (!pnModAPILoad('dynamicdata', 'user'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $data['fields'] = pnModAPIFunc('dynamicdata','user','getprop',
                                   array('modid' => $modid,
                                         'itemtype' => $itemtype));
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    $data['labels'] = array(
                            'id' => pnML('ID'),
                            'label' => pnML('Label'),
                            'type' => pnML('Type'),
                            'default' => pnML('Default'),
                            'validation' => pnML('Validation'),
                            'new' => pnML('New'),
                      );

    // Specify some labels and values for display
    $data['updatebutton'] = pnVarPrepForDisplay(pnML('Update Configuration'));

    // Return the template variables defined in this function
    return $data;
}

/**
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 */
function dynamicdata_admin_updateconfig()
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from pnVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($modid,
         $itemtype,
         $dd_label,
         $dd_type,
         $dd_default,
         $dd_validation) = pnVarCleanFromInput('modid',
                                               'itemtype',
                                               'dd_label',
                                               'dd_type',
                                               'dd_default',
                                               'dd_validation');

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!pnSecConfirmAuthKey()) {
        $msg = pnML('Invalid authorization key for updating #(1) configuration',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    if (!pnModAPILoad('dynamicdata', 'user'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = pnModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));

    if (!pnModAPILoad('dynamicdata', 'admin'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','admin');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    // update old fields
    foreach ($fields as $id => $field) {
        if (empty($dd_label[$id])) {
            // delete property (and corresponding data) in pnadminapi.php
            if (!pnModAPIFunc('dynamicdata','admin','deleteprop',
                              array('prop_id' => $id))) {
                return;
            }
        } else {
        // TODO : only if necessary
            // update property in pnadminapi.php
            if (!isset($dd_default[$id])) {
                $dd_default[$id] = null;
            }
            if (!isset($dd_validation[$id])) {
                $dd_validation[$id] = null;
            }
            if (!pnModAPIFunc('dynamicdata','admin','updateprop',
                              array('prop_id' => $id,
                              //      'modid' => $modid,
                              //      'itemtype' => $itemtype,
                                    'label' => $dd_label[$id],
                                    'type' => $dd_type[$id],
                                    'default' => $dd_default[$id],
                                    'validation' => $dd_validation[$id]))) {
                return;
            }
        }
    }

    // insert new field
    if (!empty($dd_label[0]) && !empty($dd_type[0])) {
        // create new property in pnadminapi.php
        $prop_id = pnModAPIFunc('dynamicdata','admin','createprop',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype,
                                      'label' => $dd_label[0],
                                      'type' => $dd_type[0],
                                      'default' => $dd_default[0],
                                      'validation' => $dd_validation[0]));
        if (empty($prop_id)) {
            return;
        }
    }

    pnRedirect(pnModURL('dynamicdata', 'admin', 'modifyconfig',
                        array('modid' => $modid,
                              'itemtype' => $itemtype)));

    // Return
    return true;
}

/**
 * generate the common admin menu configuration
 */
function dynamicdata_admin_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();

    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = pnML('Dynamic Data Configuration');

    // Preset some status variable
    $menu['status'] = '';

    // Return the array containing the menu configuration
    return $menu;
}

// ----------------------------------------------------------------------
// Hook functions (admin GUI)
// ----------------------------------------------------------------------

/**
 * select dynamicdata for a new item - hook for ('item','new','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_newhook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = pnModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!empty($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    if (!pnModAPILoad('dynamicdata', 'user'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = pnModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        return;
    } elseif (count($fields) == 0) {
        return;
    }

    return pnTplModule('dynamicdata','admin','newhook',
                         array('fields' => $fields));
}

/**
 * modify dynamicdata for an item - hook for ('item','modify','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_modifyhook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'admin', 'modifyhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = pnModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!empty($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!empty($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }

    if (!pnModAPILoad('dynamicdata', 'user'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = pnModAPIFunc('dynamicdata','user','getall',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'itemid' => $itemid));
    if (!isset($fields) || $fields == false) {
        return;
    }

    return pnTplModule('dynamicdata','admin','modifyhook',
                         array('fields' => $fields));
}

/**
 * modify configuration for a module - hook for ('module','modifyconfig','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_modifyconfighook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyconfighook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = pnModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyconfighook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!empty($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!pnModAPILoad('dynamicdata', 'user'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = pnModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        $fields = array();
    }

    $labels = array();
    $labels['dynamicdata'] = pnML('Dynamic Data Fields');
    $labels['config'] = pnML('modify');
    $link = pnModURL('dynamicdata','admin','modifyconfig',
                     array('modid' => $modid,
                           'itemtype' => $itemtype));

    return pnTplModule('dynamicdata','admin','modifyconfighook',
                         array('labels' => $labels,
                               'link' => $link,
                               'fields' => $fields));
}

// ----------------------------------------------------------------------
// TODO: all of the 'standard' admin functions, if that makes sense someday...
//

/**
 * the main administration function
 * This function is the default function, and is called whenever the
 * module is initiated without defining arguments.  As such it can
 * be used for a number of things, but most commonly it either just
 * shows the module menu and returns or calls whatever the module
 * designer feels should be the default function (often this is the
 * view() function)
 */
function dynamicdata_admin_main()
{
    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing.  For the
    // main function we want to check that the user has at least edit privilege
    // for some item within this component, or else they won't be able to do
    // anything and so we refuse access altogether.  The lowest level of access
    // for administration depends on the particular module, but it is generally
    // either 'edit' or 'delete'
    if (!pnSecAuthAction(0, 'DynamicData::', '::', ACCESS_EDIT)) {
        $msg = pnML('Not authorized to access to #(1)',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // If you want to go directly to some default function, instead of
    // having a separate main function, you can simply call it here, and
    // use the same template for admin-main.pnd as for admin-view.pnd
    // return dynamicdata_admin_view();

    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_admin_menu();

    // Specify some other variables used in the blocklayout template
    $data['welcome'] = pnML('Welcome to the administration part of this Dynamic Data module...');

    // Return the template variables defined in this function
    return $data;

    // Note : instead of using the $data variable, you could also specify
    // the different template variables directly in your return statement :
    //
    // return array('menutitle' => ...,
    //              'welcome' => ...,
    //              ... => ...);
}

/**
 * view items
 */
function dynamicdata_admin_view()
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from pnVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    $startnum = pnVarCleanFromInput('startnum');

    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_admin_menu();

    // Initialise the variable that will hold the items, so that the template
    // doesn't need to be adapted in case of errors
    $data['items'] = array();

    // Specify some labels for display
    $data['namelabel'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLENAME'));
    $data['numberlabel'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLENUMBER'));
    $data['optionslabel'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLEOPTIONS'));
    $data['pager'] = '';

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'DynamicData::', '::', ACCESS_EDIT)) {
        $msg = pnML('Not authorized to access to #(1)',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Load API.  Note that this is loading the user API, that is because the
    // user API contains the function to obtain item information which is the
    // first thing that we need to do.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('dynamicdata', 'user')) return; // throw back

    // The user API function is called.  This takes the number of items
    // required and the first number in the list of all items, which we
    // obtained from the input and gets us the information on the appropriate
    // items.
    $items = pnModAPIFunc('dynamicdata',
                          'user',
                          'getall',
                          array('startnum' => $startnum,
                                'numitems' => pnModGetVar('dynamicdata',
                                                          'itemsperpage')));
    // Check for exceptions
    if (!isset($item) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

    // Check individual permissions for Edit / Delete
    // Note : we could use a foreach ($items as $item) here as well, as
    // shown in pnuser.php, but as an example, we'll adapt the $items array
    // 'in place', and *then* pass the complete items array to $data
    for ($i = 0; $i < count($items); $i++) {
        $item = $items[$i];
        if (pnSecAuthAction(0, 'DynamicData::', "$item[name]::$item[exid]", ACCESS_EDIT)) {
            $items[$i]['editurl'] = pnModURL('dynamicdata',
                                             'admin',
                                             'modify',
                                             array('exid' => $item['exid']));
        } else {
            $items[$i]['editurl'] = '';
        }
        $items[$i]['edittitle'] = pnML('Edit');
        if (pnSecAuthAction(0, 'DynamicData::', "$item[name]::$item[exid]", ACCESS_DELETE)) {
            $items[$i]['deleteurl'] = pnModURL('dynamicdata',
                                               'admin',
                                               'delete',
                                               array('exid' => $item['exid']));
        } else {
            $items[$i]['deleteurl'] = '';
        }
        $items[$i]['deletetitle'] = pnML('Delete');
    }

    // Add the array of items to the template variables
    $data['items'] = $items;

    // Specify some labels for display
    $data['namelabel'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLENAME'));
    $data['numberlabel'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLENUMBER'));
    $data['optionslabel'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLEOPTIONS'));

// TODO : add a pager (once it exists in BL)
    $data['pager'] = '';

    // Return the template variables defined in this function
    return $data;

    // Note : instead of using the $data variable, you could also specify
    // the different template variables directly in your return statement :
    //
    // return array('items' => ...,
    //              'namelabel' => ...,
    //              ... => ...);
}

/**
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 */
function dynamicdata_admin_new()
{
    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_admin_menu();

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'DynamicData::', '::', ACCESS_EDIT)) {
        $msg = pnML('Not authorized to access to #(1)',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Generate a one-time authorisation code for this operation
    $data['authid'] = pnSecGenAuthKey();

    // Specify some labels for display
    $data['namelabel'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLENAME'));
    $data['numberlabel'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLENUMBER'));
    $data['addbutton'] = pnVarPrepForDisplay(pnMLByKey('EXAMPLEADD'));

    $item = array();
    $item['module'] = 'dynamicdata';
    $hooks = pnModCallHooks('item','new','',$item);
    if (empty($hooks) || !is_string($hooks)) {
        $data['hooks'] = '';
    } else {
        $data['hooks'] = $hooks;
    }

    // Return the template variables defined in this function
    return $data;
}

/**
 * This is a standard function that is called with the results of the
 * form supplied by dynamicdata_admin_new() to create a new item
 * @param 'name' the name of the item to be created
 * @param 'number' the number of the item to be created
 */
function dynamicdata_admin_create($args)
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from pnVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($name,
         $number) = pnVarCleanFromInput('name',
                                        'number');

    // Admin functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // pnVarCleanFromInput().
    extract($args);

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!pnSecConfirmAuthKey()) {
        $msg = pnML('Invalid authorization key for creating new #(1) item',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Notable by its absence there is no security check here.  This is because
    // the security check is carried out within the API function and as such we
    // do not duplicate the work here

    // Load API.  All of the actual work for the creation of the new item is
    // done within the API, so we need to load that in before we can do
    // anything. If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('dynamicdata', 'admin')) return; // throw back

    // The API function is called.  Note that the name of the API function and
    // the name of this function are identical, this helps a lot when
    // programming more complex modules.  The arguments to the function are
    // passed in as their own arguments array
    $exid = pnModAPIFunc('dynamicdata',
                        'admin',
                        'create',
                        array('name' => $name,
                              'number' => $number));

    // The return value of the function is checked here, and if the function
    // suceeded then an appropriate message is posted.  Note that if the
    // function did not succeed then the API function should have already
    // posted a failure message so no action is required
    if (!isset($exid) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

    // Success
    pnSessionSetVar('statusmsg', pnMLByKey('EXAMPLECREATED'));

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    pnRedirect(pnModURL('dynamicdata', 'admin', 'view'));

    // Return
    return true;
}

/**
 * modify an item
 * This is a standard function that is called whenever an administrator
 * wishes to modify a current module item
 * @param 'exid' the id of the item to be modified
 */
function dynamicdata_admin_modify($args)
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from pnVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($modid,
         $itemtype,
         $objectid)= pnVarCleanFromInput('modid',
                                         'itemtype',
                                         'objectid');


    // Admin functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // pnVarCleanFromInput().
    extract($args);

    // At this stage we check to see if we have been passed $objectid, the
    // generic item identifier.  This could have been passed in by a hook or
    // through some other function calling this as part of a larger module, but
    // if it exists it overrides $exid
    //
    // Note that this module couuld just use $objectid everywhere to avoid all
    // of this munging of variables, but then the resultant code is less
    // descriptive, especially where multiple objects are being used.  The
    // decision of which of these ways to go is up to the module developer
    if (!empty($objectid)) {
        $modid = $objectid;
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    // Load API.  Note that this is loading the user API, that is because the
    // user API contains the function to obtain item information which is the
    // first thing that we need to do.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('dynamicdata', 'user')) return; // throw back

    // The user API function is called.  This takes the item ID which we
    // obtained from the input and gets us the information on the appropriate
    // item.  If the item does not exist we post an appropriate message and
    // return
    $item = pnModAPIFunc('dynamicdata',
                         'user',
                         'get',
                         array('modid' => $modid,
                               'itemtype' => $itemtype));
    // Check for exceptions
    if (!isset($item) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing.  However,
    // in this case we had to wait until we could obtain the item name to
    // complete the instance information so this is the first chance we get to
    // do the check
    if (!pnSecAuthAction(0, 'DynamicData::Item', "$item[name]::$exid", ACCESS_EDIT)) {
        $msg = pnML('Not authorized to modify #(1) item #(2)',
                    'DynamicData', pnVarPrepForDisplay($exid));
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Get menu variables - it helps if all of the module pages have a standard
    // menu at their head to aid in navigation
    //$menu = dynamicdata_admin_menu('modify');

    $item['module'] = 'dynamicdata';
    $hooks = pnModCallHooks('item','modify',$exid,$item);
    if (empty($hooks) || !is_string($hooks)) {
        $hooks = '';
    }
    
    // Return the template variables defined in this function
    return array('authid' => pnSecGenAuthKey(),
                 'namelabel' => pnVarPrepForDisplay(pnMLByKey('EXAMPLENAME')),
                 'numberlabel' => pnVarPrepForDisplay(pnMLByKey('EXAMPLENUMBER')),
                 'updatebutton' => pnVarPrepForDisplay(pnMLByKey('EXAMPLEUPDATE')),
                 'hooks' => $hooks,
                 'item' => $item);
}

/**
 * This is a standard function that is called with the results of the
 * form supplied by dynamicdata_admin_modify() to update a current item
 * @param 'exid' the id of the item to be updated
 * @param 'name' the name of the item to be updated
 * @param 'number' the number of the item to be updated
 */
function dynamicdata_admin_update($args)
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from pnVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($exid,
         $objectid,
         $name,
         $number) = pnVarCleanFromInput('exid',
                                        'objectid',
                                        'name',
                                        'number');

    // User functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // pnVarCleanFromInput().
    extract($args);

    // At this stage we check to see if we have been passed $objectid, the
    // generic item identifier.  This could have been passed in by a hook or
    // through some other function calling this as part of a larger module, but
    // if it exists it overrides $exid
    //
    // Note that this module couuld just use $objectid everywhere to avoid all
    // of this munging of variables, but then the resultant code is less
    // descriptive, especially where multiple objects are being used.  The
    // decision of which of these ways to go is up to the module developer
    if (!empty($objectid)) {
        $exid = $objectid;
    }

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!pnSecConfirmAuthKey()) {
        $msg = pnML('Invalid authorization key for updating #(1) item #(2)',
                    'DynamicData', pnVarPrepForDisplay($exid));
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Notable by its absence there is no security check here.  This is because
    // the security check is carried out within the API function and as such we
    // do not duplicate the work here

    // Load API.  All of the actual work for the update of the new item is done
    // within the API, so we need to load that in before we can do anything.
    // If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('dynamicdata', 'admin')) return; // throw back

    // The API function is called.  Note that the name of the API function and
    // the name of this function are identical, this helps a lot when
    // programming more complex modules.  The arguments to the function are
    // passed in as their own arguments array.
    //
    // The return value of the function is checked here, and if the function
    // suceeded then an appropriate message is posted.  Note that if the
    // function did not succeed then the API function should have already
    // posted a failure message so no action is required
    if(!pnModAPIFunc('dynamicdata',
                    'admin',
                    'update',
                    array('exid' => $exid,
                          'name' => $name,
                          'number' => $number))) {
        return; // throw back
    }
    pnSessionSetVar('statusmsg', pnMLByKey('EXAMPLEUPDATED'));

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    pnRedirect(pnModURL('dynamicdata', 'admin', 'view'));

    // Return
    return true;
}

/**
 * delete item
 * This is a standard function that is called whenever an administrator
 * wishes to delete a current module item.  Note that this function is
 * the equivalent of both of the modify() and update() functions above as
 * it both creates a form and processes its output.  This is fine for
 * simpler functions, but for more complex operations such as creation and
 * modification it is generally easier to separate them into separate
 * functions.  There is no requirement in the PostNuke MDG to do one or the
 * other, so either or both can be used as seen appropriate by the module
 * developer
 * @param 'exid' the id of the item to be deleted
 * @param 'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete($args)
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from pnVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($exid,
         $objectid,
         $confirm) = pnVarCleanFromInput('exid',
                                         'objectid',
                                         'confirm');


    // User functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // pnVarCleanFromInput().
    extract($args);

     // At this stage we check to see if we have been passed $objectid, the
     // generic item identifier.  This could have been passed in by a hook or
     // through some other function calling this as part of a larger module, but
     // if it exists it overrides $exid
     //
     // Note that this module couuld just use $objectid everywhere to avoid all
     // of this munging of variables, but then the resultant code is less
     // descriptive, especially where multiple objects are being used.  The
     // decision of which of these ways to go is up to the module developer
     if (!empty($objectid)) {
         $exid = $objectid;
     }

    // Load API.  Note that this is loading the user API, that is because the
    // user API contains the function to obtain item information which is the
    // first thing that we need to do.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('dynamicdata', 'user')) return; // throw back

    // The user API function is called.  This takes the item ID which we
    // obtained from the input and gets us the information on the appropriate
    // item.  If the item does not exist we post an appropriate message and
    // return
    $item = pnModAPIFunc('dynamicdata',
                         'user',
                         'get',
                         array('exid' => $exid));
    // Check for exceptions
    if (!isset($item) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing.  However,
    // in this case we had to wait until we could obtain the item name to
    // complete the instance information so this is the first chance we get to
    // do the check
    if (!pnSecAuthAction(0, 'DynamicData::Item', "$item[name]::$exid", ACCESS_DELETE)) {
        $msg = pnML('Not authorized to delete #(1) item #(2)',
                    'DynamicData', pnVarPrepForDisplay($exid));
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Check for confirmation.
    if (empty($confirm)) {
        // No confirmation yet - display a suitable form to obtain confirmation
        // of this action from the user

        // Initialise the $data variable that will hold the data to be used in
        // the blocklayout template, and get the common menu configuration - it
        // helps if all of the module pages have a standard menu at the top to
        // support easy navigation
        $data = dynamicdata_admin_menu();

        // Specify for which item you want confirmation
        $data['exid'] = $exid;

        // Add some other data you'll want to display in the template
        $data['confirmtext'] = pnML('Confirm deleting this item ?');
        $data['itemid'] =  pnML('Item ID');
        $data['namelabel'] =  pnMLByKey('EXAMPLENAME');
        $data['namevalue'] = pnVarPrepForDisplay($item['name']);
        $data['confirmbutton'] = pnML('Confirm');

        // Generate a one-time authorisation code for this operation
        $data['authid'] = pnSecGenAuthKey();

        // Return the template variables defined in this function
        return $data;
    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!pnSecConfirmAuthKey()) {
        $msg = pnML('Invalid authorization key for deleting #(1) item #(2)',
                    'DynamicData', pnVarPrepForDisplay($exid));
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Load API.  All of the actual work for the deletion of the item is done
    // within the API, so we need to load that in before before we can do
    // anything.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('dynamicdata', 'admin')) return; // throw back

    // The API function is called.  Note that the name of the API function and
    // the name of this function are identical, this helps a lot when
    // programming more complex modules.  The arguments to the function are
    // passed in as their own arguments array.
    //
    // The return value of the function is checked here, and if the function
    // suceeded then an appropriate message is posted.  Note that if the
    // function did not succeed then the API function should have already
    // posted a failure message so no action is required
    if (!pnModAPIFunc('dynamicdata',
                     'admin',
                     'delete',
                     array('exid' => $exid))) {
        return; // throw back
    }
    pnSessionSetVar('statusmsg', pnMLByKey('EXAMPLEDELETED'));


    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    pnRedirect(pnModURL('dynamicdata', 'admin', 'view'));

    // Return
    return true;
}

//
// TODO: all of the 'standard' admin functions, if that makes sense someday...
// ----------------------------------------------------------------------


?>
