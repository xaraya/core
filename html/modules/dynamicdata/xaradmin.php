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

require_once 'modules/dynamicdata/class/objects.php';

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

    $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $itemid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label = $object['label'];
        $param = $object['urlparam'];
    } else {
        return;
    }

    $data = dynamicdata_admin_menu();

/*
    $mylist = new Dynamic_Object_List(array('objectid' => $itemid,
                                            'moduleid' => $modid,
                                            'itemtype' => $itemtype));
    $data['mylist'] = & $mylist;
*/

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
            $seenmod[$object['moduleid']] = 1;
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

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', '$modid:$itemtype:', ACCESS_ADD)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $data = dynamicdata_admin_menu();

    $data['object'] = new Dynamic_Object(array('objectid' => $objectid,
                                               'moduleid' => $modid,
                                               'itemtype' => $itemtype,
                                               'itemid'   => $itemid));

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

    if (!xarSecConfirmAuthKey()) return;

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

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    $isvalid = $myobject->checkInput();

    if (!empty($preview) || !$isvalid) {
        $data = dynamicdata_admin_menu();

        $data['object'] = & $myobject;

        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;
        return xarTplModule('dynamicdata','admin','new', $data);
    }

    $itemid = $myobject->createItem();

    if (empty($itemid)) return; // throw back

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                  array('itemid' => $myobject->objectid)));

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

    $data = dynamicdata_admin_menu();

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    $myobject->getItem();
    $data['object'] = & $myobject;

    $data['objectid'] = $myobject->objectid;
    $data['itemid'] = $itemid;
    $data['authid'] = xarSecGenAuthKey();

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

    if (!xarSecConfirmAuthKey()) return;

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($preview)) {
        $preview = 0;
    }

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    $myobject->getItem();

    $isvalid = $myobject->checkInput();

    if (!empty($preview) || !$isvalid) {
        $data = dynamicdata_admin_menu();
        $data['object'] = & $myobject;

        $data['objectid'] = $myobject->objectid;
        $data['itemid'] = $itemid;
        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;

        return xarTplModule('dynamicdata','admin','modify', $data);
    }

    $itemid = $myobject->updateItem();

    if (!isset($itemid)) return; // throw back

    // check if we need to set a module alias (or remove it) for short URLs
    if ($myobject->objectid == 1) {
        $name = $myobject->properties['name']->value;
        $alias = xarModGetAlias($name);
        $isalias = $myobject->properties['isalias']->value;
        if (!empty($isalias)) {
            // no alias defined yet, so we create one
            if ($alias == $name) {
                xarModSetAlias($name,'dynamicdata');
            }
        } else {
            // this was a defined alias, so we remove it
            if ($alias == 'dynamicdata') {
                xarModDelAlias($name,'dynamicdata');
            }
        }
    }

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                  array('itemid' => $myobject->objectid)));

    // Return
    return true;
}


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

    $myobject = new Dynamic_Object(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    $myobject->getItem();

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', '$modid:$itemtype:$itemid', ACCESS_DELETE)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (empty($confirm)) {
        $data = dynamicdata_admin_menu();
        $data['object'] = & $myobject;
        if ($myobject->objectid == 1) {
            $mylist = new Dynamic_Object_List(array('objectid' => $itemid));
            if (count($mylist->properties) > 0) {
                $data['related'] = xarML('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
            }
        }
        $data['authid'] = xarSecGenAuthKey();

        return $data;
    }

    // If we get here it means that the user has confirmed the action

    if (!xarSecConfirmAuthKey()) return;

    // special case for a dynamic object : delete its properties too // TODO: and items
// TODO: extend to any parent-child relation ?
    if ($myobject->objectid == 1) {
        $mylist = new Dynamic_Object_List(array('objectid' => $itemid));
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = Dynamic_Property_Master::deleteProperty(array('itemid' => $propid));
        }
    }

    $itemid = $myobject->deleteItem();

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
         $itemtype,
         $details) = xarVarCleanFromInput('itemid',
                                          'modid',
                                          'itemtype',
                                          'details');

    if (empty($itemtype)) {
        $itemtype = 0;
    }

/*
    if (!empty($itemid)) {
        $where = 'objectid eq '.$itemid;
    } else {
        $where = 'moduleid eq '.$modid.' and itemtype eq '.$itemtype;
    }
    $myobject = new Dynamic_Object_List(array('objectid' => 2,
                                              'fieldlist' => array('id','label','type','default','source','validation','status','objectid','moduleid','itemtype'),
                                              'where' => $where));
    if ($myobject->items) {
        $myobject->getItems();
    }
    $data['myobject'] = & $myobject;
    //echo var_dump($myobject);
*/

    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $itemid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));

    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label =  $object['label'];
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
        $data['objectid'] = $object['objectid'];
        if (!empty($itemtype)) {
            $data['label'] = xarML('for #(1)', $object['label']);
        } else {
            $data['label'] = xarML('for #(1)', $object['label']);
        }
    }

    $data['fields'] = xarModAPIFunc('dynamicdata','user','getprop',
                                   array('modid' => $modid,
                                         'itemtype' => $itemtype));
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    // get possible data sources
    $data['sources'] = Dynamic_DataStore_Master::getDataSources();
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
                            'status' => xarML('Status'),
                            'validation' => xarML('Validation'),
                            'new' => xarML('New'),
                      );

    // Specify some labels and values for display
    $data['updatebutton'] = xarVarPrepForDisplay(xarML('Update Properties'));

    if (empty($details)) {
        $data['static'] = array();
        $data['relations'] = array();
        if (!empty($objectid)) {
            $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                             array('itemid' => $objectid,
                                                   'details' => 1));
        } else {
            $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                             array('modid' => $modid,
                                                   'itemtype' => empty($itemtype) ? null : $itemtype,
                                                   'details' => 1));
        }
        return $data;
    }

    $data['details'] = $details;

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

    if (!empty($objectid)) {
        $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                         array('itemid' => $objectid));
    } else {
        $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                         array('modid' => $modid,
                                               'itemtype' => empty($itemtype) ? null : $itemtype));
    }

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
         $dd_status,
         $dd_validation) = xarVarCleanFromInput('objectid',
                                               'modid',
                                               'itemtype',
                                               'dd_label',
                                               'dd_type',
                                               'dd_default',
                                               'dd_source',
                                               'dd_status',
                                               'dd_validation');

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSecConfirmAuthKey()) return;

    if (empty($itemtype)) {
        $itemtype = 0;
    }

    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
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

    $i = 0;
    // update old fields
    foreach ($fields as $name => $field) {
        $id = $field['id'];
        $i++;
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
                                    'status' => $dd_status[$id],
                                    'validation' => $dd_validation[$id]))) {
                return;
            }
        }
    }

    $i++;
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
                                      'order' => $i,
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
    if (!xarSecConfirmAuthKey()) return;

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
 * Export an object definition or an object item to XML
 */
function dynamicdata_admin_export($args)
{
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_ADMIN)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    list($objectid,
         $modid,
         $itemtype,
         $itemid) = xarVarCleanFromInput('objectid',
                                         'modid',
                                         'itemtype',
                                         'itemid');

    extract($args);

    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $data = dynamicdata_admin_menu();

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));

    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (!isset($object)) {
        $data['label'] = xarML('Unknown Object');
        $data['xml'] = '';
        return $data;
    }

    $objectid = $object['objectid'];
    $modid = $object['moduleid'];
    $itemtype = $object['itemtype'];
    $label =  $object['label'];

    // export object definition
    if (empty($itemid)) {
        $data['label'] = xarML('Export Object Definition for #(1)', $label);

        $xml = '';
/*
        $xml .= "<object>\n";
        foreach ($object as $key => $value) {
            $xml .= "  <$key>" . xarVarPrepForDisplay($value) . "</$key>\n";
        }
        $xml .= "  <properties>\n";
        $fields = xarModAPIFunc('dynamicdata','user','getprop',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype));
        if (!isset($fields)) {
            $data['xml'] = xarML('Unknown Properties');
            return $data;
        }
        $i = 1;
        foreach ($fields as $name => $field) {
            $xml .= "    <property id=\"$i\">\n";
            foreach ($field as $key => $value) {
                $xml .= "      <$key>" . xarVarPrepForDisplay($value) . "</$key>\n";
            }
            $xml .= "    </property>\n";
            $i++;
        }
        $xml .= "  </properties>\n";
    // TODO: insert items here for some parameter
        $xml .= "</object>\n";
*/

/*      // this returns a bit too much :-)
        $xml .= "<object>\n";
        foreach (get_object_vars($myobject) as $key => $value) {
            if (is_array($value)) {
                $xml .= "  <$key>\n";
                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        $xml .= "    <$subkey>\n";
                        //...
                        $xml .= "    </$subkey>\n";
                    } elseif (is_object($subvalue)) {
                        $xml .= "    <$subkey>\n";
                        //...
                        $xml .= "    </$subkey>\n";
                    } else {
                        $xml .= "    <$subkey>" . xarVarPrepForDisplay($subvalue) . "</$subkey>\n";
                    }
                }
                $xml .= "  </$key>\n";
            } else {
                $xml .= "  <$key>" . xarVarPrepForDisplay($value) . "</$key>\n";
            }
        }
        $xml .= "</object>\n";
*/

        $xml .= "<object>\n";
        $dynamicobject = new Dynamic_Object(array('objectid' => 1));
        foreach (array_keys($dynamicobject->properties) as $name) {
            if (isset($myobject->$name)) {
                $xml .= "  <$name>" . xarVarPrepForDisplay($myobject->$name) . "</$name>\n";
            }
        }
        $xml .= "  <properties>\n";
        $dynamicproperties = new Dynamic_Object(array('objectid' => 2));
        foreach (array_keys($myobject->properties) as $name) {
            $xml .= '      <property id="'.$name.'">' . "\n";
            foreach (array_keys($dynamicproperties->properties) as $key) {
                if (isset($myobject->properties[$name]->$key)) {
                    $xml .= "        <$key>".$myobject->properties[$name]->$key."</$key>\n";
                }
            }
            $xml .= "      </property>\n";
        }
        $xml .= "  </properties>\n";
        $xml .= "</object>\n";

    // export specific item
    } elseif (is_numeric($itemid)) {

        $data['label'] = xarML('Export Data for #(1) # #(2)', $label, $itemid);
/*
        $fields = xarModAPIFunc('dynamicdata','user','getitem',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype,
                                      'itemid' => $itemid));
        if (!isset($fields)) {
            $data['xml'] = xarML('Unknown Item');
            return $data;
        }
        $objectname = $object['name'];
        $xml = "<$objectname id=\"$itemid\">\n";
        foreach ($fields as $fieldname => $fieldvalue) {
            $xml .= "  <$fieldname>" . xarVarPrepForDisplay($fieldvalue) . "</$fieldname>\n";
        }
        $xml .= "</$objectname>\n";
*/
        $myobject->getItem();

        $xml = '<'.$myobject->name.' id="'.$itemid.'">'."\n";
        foreach (array_keys($myobject->properties) as $name) {
            $xml .= "  <$name>" . xarVarPrepForDisplay($myobject->properties[$name]->value) . "</$name>\n";
        }
        $xml .= '</'.$myobject->name.">\n";

    // export all items (better save this to file, e.g. in var/cache/...)
    } elseif ($itemid == 'all') {
        $data['label'] = xarML('Export Data for all #(1) Items', $label);

        $mylist = new Dynamic_Object_List(array('objectid' => $objectid,
                                                'moduleid' => $modid,
                                                'itemtype' => $itemtype));
        $mylist->getItems();

        $xml = "<items>\n";
        foreach ($mylist->items as $itemid => $item) {
            $xml .= '  <'.$mylist->name.' id="'.$itemid.'">'."\n";
            foreach (array_keys($mylist->properties) as $name) {
                if (isset($item[$name])) {
                    $xml .= "    <$name>" . xarVarPrepForDisplay($item[$name]) . "</$name>\n";
                } else {
                    $xml .= "    <$name></$name>\n";
                }
            }
            $xml .= '  </'.$mylist->name.">\n";
        }
        $xml .= "</items>\n";

/*
        $varDir = xarCoreGetVarDirPath();
        $outfile = $varDir . '/cache/templates/' . xarVarPrepForOS($mylist->name) . '.data.xml';
        $fp = @fopen($outfile,'w');
        if (!$fp) {
            $data['xml'] = xarML('Unable to open file');
            return $data;
        }
        fputs($fp, "<items>\n");
        foreach ($mylist->items as $itemid => $item) {
            fputs($fp, "  <".$mylist->name." id=\"$itemid\">\n");
            foreach (array_keys($mylist->properties) as $name) {
                if (isset($item[$name])) {
                    fputs($fp, "    <$name>" . xarVarPrepForDisplay($item[$name]) . "</$name>\n");
                } else {
                    fputs($fp, "    <$name></$name>\n");
                }
            }
            fputs($fp, "  </".$mylist->name.">\n");
        }
        fputs($fp, "</items>\n");
        fclose($fp);
        $xml .= xarML('Data saved to #(1)',$outfile);
*/

    } else {
        $data['label'] = xarML('Unknown Request for #(1)', $label);
        $xml = '';
    }

    $data['xml'] = xarVarPrepForDisplay($xml);

    return $data;
}

/**
 * Import an object definition or an object item from XML (not referenced in GUI for now)
 */
function dynamicdata_admin_import($args)
{
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_ADMIN)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $import = xarVarCleanFromInput('import');

    extract($args);

    $data = dynamicdata_admin_menu();
    $data['warning'] = '';
    $data['options'] = array();
    $data['authid'] = xarSecGenAuthKey();

    if (!xarModAPILoad('dynamicdata', 'admin')) return; // throw back
    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $basedir = 'modules/dynamicdata';
    $filetype = 'xml';
    $files = xarModAPIFunc('dynamicdata','admin','browse',
                           array('basedir' => $basedir,
                                 'filetype' => $filetype));
    if (!isset($files) || count($files) < 1) {
        $data['warning'] = xarML('There are currently no XML files available for import in "#(1)"',$basedir);
        return $data;
    }

    if (!empty($import)) {
        if (!xarSecConfirmAuthKey()) return;

        $found = '';
        foreach ($files as $file) {
            if ($file == $import) {
                $found = $file;
                break;
            }
        }
        if (empty($found) || !file_exists($basedir . '/' . $file)) {
            $msg = xarML('File not found');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
        $fp = @fopen($basedir . '/' . $file, 'r');
        if (!$fp) {
            $msg = xarML('Unable to open file');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }

        $what = '';
        $count = 0;
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            $count++;
            if (empty($what)) {
                if (preg_match('#<object.*>#',$line)) { // in case we import the object definition
                    $object = array();
                    $what = 'object';
                } elseif (preg_match('#<items>#',$line)) { // in case we only import data
                    $what = 'item';
                }

            } elseif ($what == 'object') {
                if (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                    if (isset($object[$key])) {
                        $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','object',xarVarPrepForDisplay($key),$count);
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
                    }
                    $object[$key] = $value;
                } elseif (preg_match('#<properties>#',$line)) {
                    // let's create the object now...
                    if (empty($object['name']) || empty($object['moduleid'])) {
                        $msg = xarML('Missing keys in object definition');
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
                    }
                    // make sure we drop the object id, because it might already exist here
                    unset($object['objectid']);
                // TODO: make sure itemtype is unique when we're dealing with fully dynamic objects
                //       (= objects that have the moduleid of dynamicdata itself)

                    $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                              $object);
                    if (!isset($objectid)) {
                        fclose($fp);
                        return;
                    }

                    // retrieve the correct itemtype if necessary
                    if ($object['itemtype'] < 0) {
                        $objectinfo = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                                    array('objectid' => $objectid));
                        $object['itemtype'] = $objectinfo['itemtype'];
                    }

                    $what = 'property';
                } elseif (preg_match('#<items>#',$line)) {
                    $what = 'item';
                } elseif (preg_match('#</object>#',$line)) {
                    $what = '';
                } else {
                    // multi-line entries not relevant here
                }

            } elseif ($what == 'property') {
                if (preg_match('#<property id="\w+">#',$line)) {
                    $property = array();
                } elseif (preg_match('#</property>#',$line)) {
                    // let's create the property now...
                    $property['objectid'] = $objectid;
                    $property['moduleid'] = $object['moduleid'];
                    $property['itemtype'] = $object['itemtype'];
                    // make sure we drop the property id, because it might already exist here
                    unset($property['id']);
                    $prop_id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                             $property);
                    if (!isset($prop_id)) {
                        fclose($fp);
                        return;
                    }
                } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                    if (isset($property[$key])) {
                        $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','property',xarVarPrepForDisplay($key),$count);
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
                    }
                    $property[$key] = $value;
                } elseif (preg_match('#</properties>#',$line)) {
                    $what = 'object';
                } elseif (preg_match('#<items>#',$line)) {
                    $what = 'item';
                } elseif (preg_match('#</object>#',$line)) {
                    $what = '';
                } else {
                    // multi-line entries not relevant here
                }

            } elseif ($what == 'item') {
                if (preg_match('#<([^> ]+) id="\d+">#',$line,$matches)) {
                    // find out what kind of item we're dealing with
                    $objectname = $matches[1];
                    // ...
                    $item = array();
                    $closeitem = $objectname;
                    $closetag = 'N/A';
                } elseif (preg_match("#</$closeitem>#",$line)) {
                    // let's create the item now...
                    // ...
                    $closeitem = 'N/A';
                    $closetag = 'N/A';
                } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                    if (isset($item[$key])) {
                        $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','item',xarVarPrepForDisplay($key),$count);
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
                    }
                    $item[$key] = $value;
                    $closetag = 'N/A';
                } elseif (preg_match('#<([^/>]+)>(.*)#',$line,$matches)) {
                    // multi-line entries *are* relevant here
                    $key = $matches[1];
                    $value = $matches[2];
                    if (isset($item[$key])) {
                        $msg = xarML('Duplicate definition for #(1) key #(2)','item',xarVarPrepForDisplay($key));
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
                    }
                    $item[$key] = $value;
                    $closetag = $key;
                } elseif (preg_match("#(.*)</$closetag>#",$line,$matches)) {
                    // multi-line entries *are* relevant here
                    $value = $matches[1];
                    if (!isset($item[$closetag])) {
                        $msg = xarML('Undefined #(1) key #(2)','item',xarVarPrepForDisplay($closetag));
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
                    }
                    $item[$closetag] .= $value;
                    $closetag = 'N/A';
                } elseif ($closetag != 'N/A') {
                    // multi-line entries *are* relevant here
                    if (!isset($item[$closetag])) {
                        $msg = xarML('Undefined #(1) key #(2)','item',xarVarPrepForDisplay($closetag));
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
                    }
                    $item[$closetag] .= $line;
                } elseif (preg_match('#</items>#',$line)) {
                    $what = 'object';
                } elseif (preg_match('#</object>#',$line)) {
                    $what = '';
                } else {
                }
            } else {
            }
        }
        fclose($fp);

        $data['warning'] = xarML('Object #(1) was successfully imported',xarVarPrepForDisplay($object['label']));
        return $data;
    }

    natsort($files);
    array_unshift($files,'');
    foreach ($files as $file) {
         $data['options'][] = array('id' => $file,
                                    'name' => $file);
    }

    return $data;
}

/**
 * Return meta data (test only)
 */
function dynamicdata_admin_meta($args)
{
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_ADMIN)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $export = xarVarCleanFromInput('export');

    extract($args);
    if (empty($export)) {
        $export = 0;
    }

    $data = dynamicdata_admin_menu();

    if (!xarModAPILoad('dynamicdata','user')) return;

    $data['tables'] = xarModAPIFunc('dynamicdata','user','getmeta');
    $data['tablelist'] = array_keys($data['tables']);

    $data['export'] = $export;
    return $data;
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

    if (!empty($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } elseif (isset($objectid)) {
        $itemid = $objectid;
    } else {
        $itemid = 0;
    }
    $object = new Dynamic_Object(array('moduleid' => $modid,
                                       'itemtype' => $itemtype,
                                       'itemid'   => $itemid));
    if (!isset($object)) return;

    // if we are in preview mode, we need to check for any preview values
    $preview = xarVarCleanFromInput('preview');
    if (!empty($preview)) {
        $object->checkInput();
    }

    return xarTplModule('dynamicdata','admin','newhook',
                         array('properties' => & $object->properties));
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

    $object = new Dynamic_Object(array('moduleid' => $modid,
                                       'itemtype' => $itemtype,
                                       'itemid'   => $itemid));
    if (!isset($object)) return;

    $object->getItem();

    // if we are in preview mode, we need to check for any preview values
    $preview = xarVarCleanFromInput('preview');
    if (!empty($preview)) {
        $object->checkInput();
    }

    return xarTplModule('dynamicdata','admin','modifyhook',
                         array('properties' => & $object->properties));
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

    if (!xarModAPILoad('dynamicdata', 'user')) return;

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

?>
