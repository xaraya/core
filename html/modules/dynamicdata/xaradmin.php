<?php
/**
 * File: $Id$
 *
 * Dynamic Data Admin Interface
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * the main administration function
 *
 */
function dynamicdata_admin_main()
{
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $data = dynamicdata_admin_menu();

    // Specify some other variables used in the blocklayout template
    $data['welcome'] = xarML('Welcome to the administration part of this Dynamic Data module...');

    // Return the template variables defined in this function
    return $data;
}

/**
 * view items
 */
function dynamicdata_admin_view($args)
{
    list($itemid,
         $modid,
         $itemtype,
         $startnum) = xarVarCleanFromInput('itemid',
                                           'modid',
                                           'itemtype',
                                           'startnum');

    extract($args);

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }

    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $itemid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
        $label = $object['label']['value'];
        $param = $object['urlparam']['value'];
        // override for system objects
        if ($objectid < 3) {
            $param = 'itemid';
        }
    } else {
        $label = xarML('Dynamic Data Objects');
        $param = '';
    }

    $data = dynamicdata_admin_menu();

    $data['objectid'] = $objectid;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['param'] = $param;
    $data['startnum'] = $startnum;
    $data['label'] = $label;

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // show other modules
    $data['modlist'] = array();
    if ($objectid == 1) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        $seenmod = array();
        foreach ($objects as $object) {
            $seenmod[$object['fields']['moduleid']['value']] = 1;
        }

        $modList = xarModGetList(array(),NULL,NULL,'category/name');
        $oldcat = '';
        for ($i = 0; $i < count($modList); $i++) {
            if (!empty($seenmod[$modList[$i]['regid']])) {
                continue;
            }
            if ($oldcat != $modList[$i]['category']) {
                $modList[$i]['header'] = $modList[$i]['category'];
                $oldcat = $modList[$i]['category'];
            } else {
                $modList[$i]['header'] = '';
            }
            if (xarSecAuthAction(0, 'DynamicData::Item', $modList[$i]['regid']."::", ACCESS_ADMIN)) {
                $modList[$i]['link'] = xarModURL('dynamicdata','admin','modifyprop',
                                                  array('modid' => $modList[$i]['regid']));
            } else {
                $modList[$i]['link'] = '';
            }
            $data['modlist'][] = $modList[$i];
        }
    }

    // Return the template variables defined in this function
    return $data;
}

/**
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 */
function dynamicdata_admin_new($args)
{
    list($objectid,
         $modid,
         $itemtype,
         $itemid,
         $preview) = xarVarCleanFromInput('objectid',
                                          'modid',
                                          'itemtype',
                                          'itemid',
                                          'preview');

    extract($args);

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }
    if (!isset($itemid)) {
        $itemid = 0;
    }
    if (empty($label)) {
        $label = xarML('Dynamic Data');
    }

    if (!xarModAPILoad('dynamicdata','user')) return;

    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
        $label =  $object['label']['value'];
    } else {
        $label = xarML('Dynamic Data Object');
    }

    $data = dynamicdata_admin_menu();

    $data['objectid'] = $objectid;
    $data['label'] = $label;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['itemid'] = $itemid; // might be coming from another module !
    $data['fields'] = array(); // we'll let the form handle it

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', '$modid:$itemtype:', ACCESS_ADD)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

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
    list($objectid,
         $modid,
         $itemtype,
         $itemid,
         $preview) = xarVarCleanFromInput('objectid',
                                          'modid',
                                          'itemtype',
                                          'itemid',
                                          'preview');
    extract($args);

    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Invalid authorization key for creating new #(1) item',
                    'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($itemid)) {
        $itemid = 0;
    }
    if (empty($preview)) {
        $preview = 0;
    }

    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
        $label =  $object['label']['value'];
    } else {
        $label = xarML('Dynamic Data Object');
    }

    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        return; // throw back
    }
    if (!xarModAPILoad('dynamicdata', 'admin')) return; // throw back

    // this fills $invalid with an array of errors, or fills $fields with the values
    $invalid = xarModAPIFunc('dynamicdata','admin','checkinput',
                             array('fields'      => &$fields, // pass by reference !
                                   'dd_function' => 'create',
                                   'extrainfo'   => array()));

//print_r($fields);
    if (!empty($preview) || count($invalid) > 0) {
        $data = dynamicdata_admin_menu();
        $data['objectid'] = $objectid;
        $data['label'] = $label;
        $data['modid'] = $modid;
        $data['itemtype'] = $itemtype;
        $data['itemid'] = $itemid; // might be coming from another module !
        $data['fields'] = $fields; // we'll handle it ourselves here !
        $data['authid'] = xarSecGenAuthKey();
    //    $data['where'] = $where; // our selection criteria
        $data['preview'] = $preview;
        return xarTplModule('dynamicdata','admin','new', $data);
    }

    $itemid = xarModAPIFunc('dynamicdata','admin','create',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'itemid' => 0, // in this case
                                  'fields' => $fields));

    if (!isset($itemid)) return; // throw back

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                  array('itemid' => $objectid)));

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
    list($objectid,
         $modid,
         $itemtype,
         $itemid)= xarVarCleanFromInput('objectid',
                                        'modid',
                                        'itemtype',
                                        'itemid');
    extract($args);

    if (empty($itemid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'item id', 'admin', 'modify', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', '$modid:$itemtype:$itemid', ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (!xarModAPILoad('dynamicdata','user')) return;

    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
        $label =  $object['label']['value'];
    } else {
        $label = xarML('Dynamic Data Object');
    }

    $where = "moduleid eq $modid and itemtype eq $itemtype";

    $data = dynamicdata_admin_menu();
    $data['objectid'] = $objectid;
    $data['label'] = $label;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['itemid'] = $itemid;
    $data['fields'] = array(); // we'll let the form handle it
    $data['where'] = $where; // our selection criteria
    $data['authid'] = xarSecGenAuthKey();
    // show a link to edit properties if we're dealing with a Dynamic Object
    if ($objectid == 1) {
        $data['proplink'] = xarModURL('dynamicdata','admin','modifyprop',
                                      array('itemid' => $itemid));
        $data['viewlink'] = xarModURL('dynamicdata','admin','view',
                                      array('itemid' => $itemid));
    } else {
        $data['proplink'] = '';
        $data['viewlink'] = '';
    }

    return $data;
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
    list($objectid,
         $modid,
         $itemtype,
         $itemid,
         $preview) = xarVarCleanFromInput('objectid',
                                          'modid',
                                          'itemtype',
                                          'itemid',
                                          'preview');

    extract($args);

    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Invalid authorization key for updating #(1) item',
                    'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($preview)) {
        $preview = 0;
    }

    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
        $label =  $object['label']['value'];
    } else {
        $label = xarML('Dynamic Data Object');
    }

    $fields = xarModAPIFunc('dynamicdata','user','getitem',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'itemid' => $itemid));
    if (!isset($fields) || $fields == false) {
        return; // throw back
    }

    if (!xarModAPILoad('dynamicdata', 'admin')) return; // throw back

    // this fills $invalid with an array of errors, or fills $fields with the values
    $invalid = xarModAPIFunc('dynamicdata','admin','checkinput',
                             array('fields'      => &$fields, // pass by reference !
                                   'dd_function' => 'update',
                                   'extrainfo'   => array()));

    if (!empty($preview) || count($invalid) > 0) {
        $data = dynamicdata_admin_menu();
        $data['objectid'] = $objectid;
        $data['label'] = $label;
        $data['modid'] = $modid;
        $data['itemtype'] = $itemtype;
        $data['itemid'] = $itemid;
        $data['fields'] = $fields; // we'll handle it ourselves here !
        $data['authid'] = xarSecGenAuthKey();
    //    $data['where'] = $where; // our selection criteria
        $data['preview'] = $preview;
        // show a link to edit properties if we're dealing with a Dynamic Object
        if ($objectid == 1) {
            $data['proplink'] = xarModURL('dynamicdata','admin','modifyprop',
                                          array('itemid' => $itemid));
            $data['viewlink'] = xarModURL('dynamicdata','admin','view',
                                          array('itemid' => $itemid));
        } else {
            $data['proplink'] = '';
            $data['viewlink'] = '';
        }

        return xarTplModule('dynamicdata','admin','modify', $data);
    }

    $itemid = xarModAPIFunc('dynamicdata','admin','update',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'itemid' => $itemid,
                                  'fields' => $fields));

    if (!isset($itemid)) return; // throw back

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                  array('itemid' => $objectid)));

    // Return
    return true;
}

// ----------------------------------------------------------------------
// Properties functions
// ----------------------------------------------------------------------

/**
 * Modify the dynamic properties for a module + itemtype
 */
function dynamicdata_admin_modifyprop()
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

    list($itemid,
         $modid,
         $itemtype) = xarVarCleanFromInput('itemid',
                                           'modid',
                                           'itemtype');

    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $itemid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
        $label =  $object['label']['value'];
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'modifyprop', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    $modinfo = xarModGetInfo($modid);
    if (!isset($object)) {
        $data['objectid'] = 0;
        if (!empty($itemtype)) {
            $data['label'] = xarML('for module #(1) - item type #(2)', $modinfo['displayname'], $itemtype);
        } else {
            $data['label'] = xarML('for module #(1)', $modinfo['displayname']);
        }
    } else {
        $data['objectid'] = $object['id']['value'];
        if (!empty($itemtype)) {
            $data['label'] = xarML('for #(1) (module #(2) - item type #(3))', $object['label']['value'], $modinfo['displayname'], $itemtype);
        } else {
            $data['label'] = xarML('for #(1) (module #(2))', $object['label']['value'], $modinfo['displayname']);
        }
    }

    $data['fields'] = xarModAPIFunc('dynamicdata','user','getprop',
                                   array('modid' => $modid,
                                         'itemtype' => $itemtype));
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    // get possible data sources
    $data['sources'] = xarModAPIFunc('dynamicdata','user','getsources');
    if (empty($data['sources'])) {
        $data['sources'] = array();
    }

    $data['labels'] = array(
                            'id' => xarML('ID'),
                            'name' => xarML('Name'),
                            'label' => xarML('Label'),
                            'type' => xarML('Property Type'),
                            'default' => xarML('Default'),
                            'source' => xarML('Data Source'),
                            'validation' => xarML('Validation'),
                            'new' => xarML('New'),
                      );

    // Specify some labels and values for display
    $data['updatebutton'] = xarVarPrepForDisplay(xarML('Update Properties'));

// TODO: allow modules to specify their own properties
    // (try to) show the "static" properties, corresponding to fields in dedicated
    // tables for this module
    $data['static'] = xarModAPIFunc('dynamicdata','user','getstatic',
                                   array('modid' => $modid,
                                         'itemtype' => $itemtype));
    if (!isset($data['static']) || $data['static'] == false) {
        $data['static'] = array();
        $data['tables'] = array();
    } else {
        $data['tables'] = array();
        foreach ($data['static'] as $field) {
            if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $data['tables'][$table] = array('tname' => $table);
            }
        }
    }

    $data['statictitle'] = xarML('Static Properties (guessed from module table definitions for now)');

// TODO: allow other kinds of relationships than hooks
    // (try to) get the relationships between this module and others
    $data['relations'] = xarModAPIFunc('dynamicdata','user','getrelations',
                                       array('modid' => $modid,
                                             'itemtype' => $itemtype));
    if (!isset($data['relations']) || $data['relations'] == false) {
        $data['relations'] = array();
    }

    $data['relationstitle'] = xarML('Relationships with other Modules/Properties (only item display hooks for now)');
    $data['labels']['module'] = xarML('Module');
    $data['labels']['linktype'] = xarML('Link Type');
    $data['labels']['linkfrom'] = xarML('From');
    $data['labels']['linkto'] = xarML('To');

//    $data['where'] = "moduleid eq $modid and itemtype eq $itemtype";

    // Return the template variables defined in this function
    return $data;
}

/**
 * Update the dynamic properties for a module + itemtype
 */
function dynamicdata_admin_updateprop()
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    list($objectid,
         $modid,
         $itemtype,
         $dd_label,
         $dd_type,
         $dd_default,
         $dd_source,
         $dd_validation) = xarVarCleanFromInput('objectid',
                                               'modid',
                                               'itemtype',
                                               'dd_label',
                                               'dd_type',
                                               'dd_default',
                                               'dd_source',
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

    if (empty($itemtype)) {
        $itemtype = 0;
    }

    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
    } elseif (!empty($modid)) {
        $modinfo = xarModGetInfo($modid);
        if (!empty($modinfo['name'])) {
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            if (!xarModAPILoad('dynamicdata','admin')) return;
            $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                      array('moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)));
            if (!isset($objectid)) return;
        }
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'updateprop', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));

    if (!xarModAPILoad('dynamicdata', 'admin')) return;

    // update old fields
    foreach ($fields as $name => $field) {
        $id = $field['id'];
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
                                    'source' => $dd_source[$id],
                                    'validation' => $dd_validation[$id]))) {
                return;
            }
        }
    }

    // insert new field
    if (!empty($dd_label[0]) && !empty($dd_type[0])) {
        // create new property in xaradminapi.php
        $name = strtolower($dd_label[0]);
        $name = preg_replace('/\s+/','_',$name);
        $prop_id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                array('name' => $name,
                                      'label' => $dd_label[0],
                                      'objectid' => $objectid,
                                      'moduleid' => $modid,
                                      'itemtype' => $itemtype,
                                      'type' => $dd_type[0],
                                      'default' => $dd_default[0],
                                      'source' => $dd_source[0],
                                      'status' => 1,
                                      'order' => 0,
                                      'validation' => $dd_validation[0]));
        if (empty($prop_id)) {
            return;
        }
    }

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                        array('modid' => $modid,
                              'itemtype' => $itemtype)));

    // Return
    return true;
}

/**
 * Import the dynamic properties for a module + itemtype from a static table
 */
function dynamicdata_admin_importprops()
{
    list($objectid,
         $modid,
         $itemtype,
         $table) = xarVarCleanFromInput('objectid',
                                        'modid',
                                        'itemtype',
                                        'table');
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'importprop', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Invalid authorization key for importing #(1) configuration',
                    'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    if (!xarModAPILoad('dynamicdata', 'admin')) return;

    if (!xarModAPIFunc('dynamicdata','admin','importproperties',
                       array('modid' => $modid,
                             'itemtype' => $itemtype,
                             'table' => $table,
                             'objectid' => $objectid))) {
        return;
    }

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                  array('modid' => $modid,
                                        'itemtype' => $itemtype)));
}

/**
 * generate the common admin menu configuration
 */
function dynamicdata_admin_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();

    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = xarML('Dynamic Data Administration');

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
    foreach (array_keys($fields) as $name) {
        $fields[$name]['value'] = $fields[$name]['default'];
    }

    // if we are in preview mode, we need to check for any preview values
    $preview = xarVarCleanFromInput('preview');
    if (!empty($preview)) {
        foreach ($fields as $name => $field) {
            $id = $field['id'];
            $value = xarVarCleanFromInput('dd_'.$id);
            if (isset($value)) {
                $fields[$name]['value'] = $value;
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
            foreach ($fields as $name => $field) {
                $id = $field['id'];
                $value = xarVarCleanFromInput('dd_'.$id);
                if (isset($value)) {
                    $fields[$name]['value'] = $value;
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

    $labels = array(
                    'id' => xarML('ID'),
                    'label' => xarML('Label'),
                    'type' => xarML('Field Format'),
                    'default' => xarML('Default'),
                    'source' => xarML('Data Source'),
                    'validation' => xarML('Validation'),
                   );

    $labels['dynamicdata'] = xarML('Dynamic Data Fields');
    $labels['config'] = xarML('modify');
    $link = xarModURL('dynamicdata','admin','modifyprop',
                     array('modid' => $modid,
                           'itemtype' => $itemtype));

    return xarTplModule('dynamicdata','admin','modifyconfighook',
                         array('labels' => $labels,
                               'link' => $link,
                               'fields' => $fields));
}

// ----------------------------------------------------------------------
// Property Types functions (*cough*)
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

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    if (!xarModAPILoad('dynamicdata', 'user')) return;

    // Get the defined property types from somewhere...
    $data['fields'] = xarModAPIFunc('dynamicdata','user','getproptypes');
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    $data['labels'] = array(
                            'id' => xarML('ID'),
                            'name' => xarML('Name'),
                            'label' => xarML('Description'),
                            'informat' => xarML('Input Format'),
                            'outformat' => xarML('Display Format'),
                            'validation' => xarML('Validation'),
                        // etc.
                            'new' => xarML('New'),
                      );

    // Specify some labels and values for display
    $data['updatebutton'] = xarVarPrepForDisplay(xarML('Update Property Types'));

    // Return the template variables defined in this function
    return $data;
}

/**
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 */
function dynamicdata_admin_updateconfig()
{
    return 'insert update code for property types here ?';
}


// ----------------------------------------------------------------------
// TODO: all of the 'standard' admin functions, if that makes sense someday...
//

/**
 * delete item
 * @param 'itemid' the id of the item to be deleted
 * @param 'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete($args)
{
    list($objectid,
         $modid,
         $itemtype,
         $itemid,
         $confirm) = xarVarCleanFromInput('objectid',
                                         'modid',
                                         'itemtype',
                                         'itemid',
                                         'confirm');
    extract($args);

    if (empty($itemid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'item id', 'admin', 'modify', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    if (!xarModAPILoad('dynamicdata','user')) return;

    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
        $label =  $object['label']['value'];
    } else {
        $label = xarML('Dynamic Data Object');
    }

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', '$modid:$itemtype:$itemid', ACCESS_DELETE)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (empty($confirm)) {
        $data = dynamicdata_admin_menu();
        $data['objectid'] = $objectid;
        $data['label'] = $label;
        $data['modid'] = $modid;
        $data['itemtype'] = $itemtype;
        $data['itemid'] = $itemid;
        $data['authid'] = xarSecGenAuthKey();

        return $data;
    }

    // If we get here it means that the user has confirmed the action

    if (!xarSecConfirmAuthKey()) {
        $msg = xarML('Invalid authorization key for deleting #(1) item #(2)',
                    'DynamicData', xarVarPrepForDisplay($itemid));
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    return 'To be continued...';

/*
    if (!xarModAPILoad('dynamicdata', 'admin')) return; // throw back

    if (!xarModAPIFunc('dynamicdata',
                     'admin',
                     'delete',
                     array('itemid' => $itemid))) {
        return; // throw back
    }

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view'));

    // Return
    return true;
*/

}

//
// TODO: all of the 'standard' admin functions, if that makes sense someday...
// ----------------------------------------------------------------------


?>
