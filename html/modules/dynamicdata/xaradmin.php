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
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_ADMIN)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    list($modid,
         $itemtype) = xarVarCleanFromInput('modid',
                                          'itemtype');
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'modifyconfig', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
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
    $data['authid'] = xarSecGenAuthKey();

    $modinfo = xarModGetInfo($modid);
    $data['module'] = xarML('for module "#(1)"', $modinfo['displayname']);
    if (!empty($itemtype)) {
        $data['module'] .= ' ' . xarML('type #(1)', $itemtype);
    }

    $data['newlink'] = xarModURL('dynamicdata','admin','newprop',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype));
    $data['formlink'] = xarModURL('dynamicdata','admin','viewform',
                                 array('modid' => $modid,
                                      'itemtype' => $itemtype));

    if (!xarModAPILoad('dynamicdata', 'user'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $data['fields'] = xarModAPIFunc('dynamicdata','user','getprop',
                                   array('modid' => $modid,
                                         'itemtype' => $itemtype));
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    $data['labels'] = array(
                            'id' => xarML('ID'),
                            'label' => xarML('Label'),
                            'type' => xarML('Type'),
                            'default' => xarML('Default'),
                            'validation' => xarML('Validation'),
                            'new' => xarML('New'),
                      );

    // Specify some labels and values for display
    $data['updatebutton'] = xarVarPrepForDisplay(xarML('Update Configuration'));

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
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($modid,
         $itemtype,
         $dd_label,
         $dd_type,
         $dd_default,
         $dd_validation) = xarVarCleanFromInput('modid',
                                               'itemtype',
                                               'dd_label',
                                               'dd_type',
                                               'dd_default',
                                               'dd_validation');

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Invalid authorization key for updating #(1) configuration',
                    'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    if (!xarModAPILoad('dynamicdata', 'user'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));

    if (!xarModAPILoad('dynamicdata', 'admin'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','admin');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    // update old fields
    foreach ($fields as $id => $field) {
        if (empty($dd_label[$id])) {
            // delete property (and corresponding data) in xaradminapi.php
            if (!xarModAPIFunc('dynamicdata','admin','deleteprop',
                              array('prop_id' => $id))) {
                return;
            }
        } else {
        // TODO : only if necessary
            // update property in xaradminapi.php
            if (!isset($dd_default[$id])) {
                $dd_default[$id] = null;
            }
            if (!isset($dd_validation[$id])) {
                $dd_validation[$id] = null;
            }
            if (!xarModAPIFunc('dynamicdata','admin','updateprop',
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
        // create new property in xaradminapi.php
        $prop_id = xarModAPIFunc('dynamicdata','admin','createprop',
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

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyconfig',
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
    $menu['menutitle'] = xarML('Dynamic Data Configuration');

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
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!empty($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    if (!xarModAPILoad('dynamicdata', 'user'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        return;
    } elseif (count($fields) == 0) {
        return;
    }

    // prefill the values with defaults (if any)
    foreach (array_keys($fields) as $id) {
        $fields[$id]['value'] = $fields[$id]['default'];
    }

    // if we are in preview mode, we need to check for any preview values
    $preview = xarVarCleanFromInput('preview');
    if (!empty($preview)) {
        foreach (array_keys($fields) as $id) {
            $value = xarVarCleanFromInput('dd_'.$id);
            if (isset($value)) {
                $fields[$id]['value'] = $value;
            }
        }
    }

    return xarTplModule('dynamicdata','admin','newhook',
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
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'admin', 'modifyhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
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

    if (!xarModAPILoad('dynamicdata', 'user'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = xarModAPIFunc('dynamicdata','user','getall',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'itemid' => $itemid));
    if (!isset($fields) || $fields == false) {
        return;
    }

    // if we are in preview mode, we need to check for any preview values
    if (is_array($fields) && count($fields) > 0) {
        $preview = xarVarCleanFromInput('preview');
        if (!empty($preview)) {
            foreach (array_keys($fields) as $id) {
                $value = xarVarCleanFromInput('dd_'.$id);
                if (isset($value)) {
                    $fields[$id]['value'] = $value;
                }
            }
        }
    }

    return xarTplModule('dynamicdata','admin','modifyhook',
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
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyconfighook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyconfighook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!empty($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!xarModAPILoad('dynamicdata', 'user'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        $fields = array();
    }

    $labels = array();
    $labels['dynamicdata'] = xarML('Dynamic Data Fields');
    $labels['config'] = xarML('modify');
    $link = xarModURL('dynamicdata','admin','modifyconfig',
                     array('modid' => $modid,
                           'itemtype' => $itemtype));

    return xarTplModule('dynamicdata','admin','modifyconfighook',
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
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // If you want to go directly to some default function, instead of
    // having a separate main function, you can simply call it here, and
    // use the same template for admin-main.xd as for admin-view.xd
    // return dynamicdata_admin_view();

    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_admin_menu();

    // Specify some other variables used in the blocklayout template
    $data['welcome'] = xarML('Welcome to the administration part of this Dynamic Data module...');

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
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    $startnum = xarVarCleanFromInput('startnum');

    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_admin_menu();

    // Initialise the variable that will hold the items, so that the template
    // doesn't need to be adapted in case of errors
    $data['items'] = array();

    // Specify some labels for display
    $data['namelabel'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLENAME'));
    $data['numberlabel'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLENUMBER'));
    $data['optionslabel'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLEOPTIONS'));
    $data['pager'] = '';

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Load API.  Note that this is loading the user API, that is because the
    // user API contains the function to obtain item information which is the
    // first thing that we need to do.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    // The user API function is called.  This takes the number of items
    // required and the first number in the list of all items, which we
    // obtained from the input and gets us the information on the appropriate
    // items.
    $items = xarModAPIFunc('dynamicdata',
                          'user',
                          'getall',
                          array('startnum' => $startnum,
                                'numitems' => xarModGetVar('dynamicdata',
                                                          'itemsperpage')));
    // Check for exceptions
    if (!isset($item) && xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

    // Check individual permissions for Edit / Delete
    // Note : we could use a foreach ($items as $item) here as well, as
    // shown in xaruser.php, but as an example, we'll adapt the $items array
    // 'in place', and *then* pass the complete items array to $data
    for ($i = 0; $i < count($items); $i++) {
        $item = $items[$i];
        if (xarSecAuthAction(0, 'DynamicData::', "$item[name]::$item[exid]", ACCESS_EDIT)) {
            $items[$i]['editurl'] = xarModURL('dynamicdata',
                                             'admin',
                                             'modify',
                                             array('exid' => $item['exid']));
        } else {
            $items[$i]['editurl'] = '';
        }
        $items[$i]['edittitle'] = xarML('Edit');
        if (xarSecAuthAction(0, 'DynamicData::', "$item[name]::$item[exid]", ACCESS_DELETE)) {
            $items[$i]['deleteurl'] = xarModURL('dynamicdata',
                                               'admin',
                                               'delete',
                                               array('exid' => $item['exid']));
        } else {
            $items[$i]['deleteurl'] = '';
        }
        $items[$i]['deletetitle'] = xarML('Delete');
    }

    // Add the array of items to the template variables
    $data['items'] = $items;

    // Specify some labels for display
    $data['namelabel'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLENAME'));
    $data['numberlabel'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLENUMBER'));
    $data['optionslabel'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLEOPTIONS'));

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
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    // Specify some labels for display
    $data['namelabel'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLENAME'));
    $data['numberlabel'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLENUMBER'));
    $data['addbutton'] = xarVarPrepForDisplay(xarMLByKey('EXAMPLEADD'));

    $item = array();
    $item['module'] = 'dynamicdata';
    $hooks = xarModCallHooks('item','new','',$item);
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
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($name,
         $number) = xarVarCleanFromInput('name',
                                        'number');

    // Admin functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // xarVarCleanFromInput().
    extract($args);

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Invalid authorization key for creating new #(1) item',
                    'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Notable by its absence there is no security check here.  This is because
    // the security check is carried out within the API function and as such we
    // do not duplicate the work here

    // Load API.  All of the actual work for the creation of the new item is
    // done within the API, so we need to load that in before we can do
    // anything. If the API fails to load the raised exception is thrown back to PostNuke
    if (!xarModAPILoad('dynamicdata', 'admin')) return; // throw back

    // The API function is called.  Note that the name of the API function and
    // the name of this function are identical, this helps a lot when
    // programming more complex modules.  The arguments to the function are
    // passed in as their own arguments array
    $exid = xarModAPIFunc('dynamicdata',
                        'admin',
                        'create',
                        array('name' => $name,
                              'number' => $number));

    // The return value of the function is checked here, and if the function
    // suceeded then an appropriate message is posted.  Note that if the
    // function did not succeed then the API function should have already
    // posted a failure message so no action is required
    if (!isset($exid) && xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

    // Success
    xarSessionSetVar('statusmsg', xarMLByKey('EXAMPLECREATED'));

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view'));

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
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($modid,
         $itemtype,
         $objectid)= xarVarCleanFromInput('modid',
                                         'itemtype',
                                         'objectid');


    // Admin functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // xarVarCleanFromInput().
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
    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    // The user API function is called.  This takes the item ID which we
    // obtained from the input and gets us the information on the appropriate
    // item.  If the item does not exist we post an appropriate message and
    // return
    $item = xarModAPIFunc('dynamicdata',
                         'user',
                         'get',
                         array('modid' => $modid,
                               'itemtype' => $itemtype));
    // Check for exceptions
    if (!isset($item) && xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing.  However,
    // in this case we had to wait until we could obtain the item name to
    // complete the instance information so this is the first chance we get to
    // do the check
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$item[name]::$exid", ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Get menu variables - it helps if all of the module pages have a standard
    // menu at their head to aid in navigation
    //$menu = dynamicdata_admin_menu('modify');

    $item['module'] = 'dynamicdata';
    $hooks = xarModCallHooks('item','modify',$exid,$item);
    if (empty($hooks) || !is_string($hooks)) {
        $hooks = '';
    }
    
    // Return the template variables defined in this function
    return array('authid' => xarSecGenAuthKey(),
                 'namelabel' => xarVarPrepForDisplay(xarMLByKey('EXAMPLENAME')),
                 'numberlabel' => xarVarPrepForDisplay(xarMLByKey('EXAMPLENUMBER')),
                 'updatebutton' => xarVarPrepForDisplay(xarMLByKey('EXAMPLEUPDATE')),
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
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($exid,
         $objectid,
         $name,
         $number) = xarVarCleanFromInput('exid',
                                        'objectid',
                                        'name',
                                        'number');

    // User functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // xarVarCleanFromInput().
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
    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Invalid authorization key for updating #(1) item #(2)',
                    'DynamicData', xarVarPrepForDisplay($exid));
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Notable by its absence there is no security check here.  This is because
    // the security check is carried out within the API function and as such we
    // do not duplicate the work here

    // Load API.  All of the actual work for the update of the new item is done
    // within the API, so we need to load that in before we can do anything.
    // If the API fails to load the raised exception is thrown back to PostNuke
    if (!xarModAPILoad('dynamicdata', 'admin')) return; // throw back

    // The API function is called.  Note that the name of the API function and
    // the name of this function are identical, this helps a lot when
    // programming more complex modules.  The arguments to the function are
    // passed in as their own arguments array.
    //
    // The return value of the function is checked here, and if the function
    // suceeded then an appropriate message is posted.  Note that if the
    // function did not succeed then the API function should have already
    // posted a failure message so no action is required
    if(!xarModAPIFunc('dynamicdata',
                    'admin',
                    'update',
                    array('exid' => $exid,
                          'name' => $name,
                          'number' => $number))) {
        return; // throw back
    }
    xarSessionSetVar('statusmsg', xarMLByKey('EXAMPLEUPDATED'));

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view'));

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
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($exid,
         $objectid,
         $confirm) = xarVarCleanFromInput('exid',
                                         'objectid',
                                         'confirm');


    // User functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // xarVarCleanFromInput().
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
    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    // The user API function is called.  This takes the item ID which we
    // obtained from the input and gets us the information on the appropriate
    // item.  If the item does not exist we post an appropriate message and
    // return
    $item = xarModAPIFunc('dynamicdata',
                         'user',
                         'get',
                         array('exid' => $exid));
    // Check for exceptions
    if (!isset($item) && xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing.  However,
    // in this case we had to wait until we could obtain the item name to
    // complete the instance information so this is the first chance we get to
    // do the check
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$item[name]::$exid", ACCESS_DELETE)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
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
        $data['confirmtext'] = xarML('Confirm deleting this item ?');
        $data['itemid'] =  xarML('Item ID');
        $data['namelabel'] =  xarMLByKey('EXAMPLENAME');
        $data['namevalue'] = xarVarPrepForDisplay($item['name']);
        $data['confirmbutton'] = xarML('Confirm');

        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSecGenAuthKey();

        // Return the template variables defined in this function
        return $data;
    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Invalid authorization key for deleting #(1) item #(2)',
                    'DynamicData', xarVarPrepForDisplay($exid));
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Load API.  All of the actual work for the deletion of the item is done
    // within the API, so we need to load that in before before we can do
    // anything.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!xarModAPILoad('dynamicdata', 'admin')) return; // throw back

    // The API function is called.  Note that the name of the API function and
    // the name of this function are identical, this helps a lot when
    // programming more complex modules.  The arguments to the function are
    // passed in as their own arguments array.
    //
    // The return value of the function is checked here, and if the function
    // suceeded then an appropriate message is posted.  Note that if the
    // function did not succeed then the API function should have already
    // posted a failure message so no action is required
    if (!xarModAPIFunc('dynamicdata',
                     'admin',
                     'delete',
                     array('exid' => $exid))) {
        return; // throw back
    }
    xarSessionSetVar('statusmsg', xarMLByKey('EXAMPLEDELETED'));


    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view'));

    // Return
    return true;
}

//
// TODO: all of the 'standard' admin functions, if that makes sense someday...
// ----------------------------------------------------------------------


?>
