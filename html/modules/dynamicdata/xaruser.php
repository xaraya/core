<?php
/**
 * File: $Id$
 *
 * Dynamic Data User Interface
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/

require_once 'modules/dynamicdata/class/objects.php';

// ----------------------------------------------------------------------
// Hook functions (user GUI)
// ----------------------------------------------------------------------


//  Ideally, people should be able to use the dynamic fields in their
//  module templates as if they were 'normal' fields -> this means
//  adapting the get() function in the user API of the module, and/or
//  using some common data retrieval function (DD) in the future...

/*  display hook is now disabled by default - use the BL tags or APIs instead */

/**
 * display dynamicdata for an item - hook for ('item','display','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_user_displayhook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'user', 'displayhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'user', 'displayhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (is_array($extrainfo) && !empty($extrainfo['module']) && is_string($extrainfo['module'])) {
        $modname = $extrainfo['module'];
    } else {
        $modname = xarModGetName();
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name ' . $modname, 'user', 'displayhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (is_array($extrainfo) && isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
// TODO: find some better way to do this !
    } elseif (xarVarIsCached('Hooks.display','itemtype')) {
        $itemtype = xarVarGetCached('Hooks.display','itemtype');
    } else {
        $itemtype = null;
    }

    if (is_array($extrainfo) && isset($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }

    $object = new Dynamic_Object(array('moduleid' => $modid,
                                       'itemtype' => $itemtype,
                                       'itemid' => $itemid));
    if (!isset($object)) return;
    $object->getItem();

// TODO: use custom template per module + itemtype ?
     return xarTplModule('dynamicdata','user','displayhook',
                         array('properties' => & $object->properties));

}

/**
 * the main user function lists the available objects defined in DD
 *
 */
function dynamicdata_user_main()
{
// Security Check
	if(!xarSecurityCheck('ViewDynamicData')) return;

    $data = dynamicdata_user_menu();

    if (!xarModAPILoad('dynamicdata','user')) return;

    // get items from the objects table
    $objects = xarModAPIFunc('dynamicdata','user','getobjects');

    $data['items'] = array();
    foreach ($objects as $itemid => $object) {
        if ($itemid < 3) continue;
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label = $object['label'];
        $data['items'][] = array(
                                 'link'     => xarModURL('dynamicdata','user','view',
                                                         array('modid' => $modid,
                                                               'itemtype' => empty($itemtype) ? null : $itemtype)),
                                 'label'    => $label
                                );
    }

    return $data;
}

/**
 * view a list of items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 */
function dynamicdata_user_view()
{
    list($objectid,
         $modid,
         $itemtype,
         $startnum) = xarVarCleanFromInput('objectid',
                                           'modid',
                                           'itemtype',
                                           'startnum');
    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (!xarModAPILoad('dynamicdata','user')) return;
    $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label = $object['label'];
        $param = $object['urlparam'];
    } else {
        $objectid = 0;
        $label = xarML('Dynamic Data Objects');
        $param = '';
    }
	if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item','$modid:$itemtype:All')) return;

    $data = dynamicdata_user_menu();
    $data['objectid'] = $objectid;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['param'] = $param;
    $data['startnum'] = $startnum;
    $data['label'] = $label;

/*  // we could also retrieve the object list here, and pass that along to the template
    $numitems = 30;
    $mylist = new Dynamic_Object_List(array('objectid' => $objectid,
                                            'moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'status'   => 1));
    $mylist->getItems(array('numitems' => $numitems,
                            'startnum' => $startnum));

    $data['object'] = & $mylist;
*/
    return $data;
}

/**
 * display an item
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @param $args an array of arguments (if called by other modules)
 */
function dynamicdata_user_display($args)
{
    list($objectid,
         $modid,
         $itemtype,
         $itemid)= xarVarCleanFromInput('objectid',
                                        'modid',
                                        'itemtype',
                                        'itemid');
    extract($args);

/*  // we could also pass along the parameters to the template, and let it retrieve the object
    // but in this case, we'd need to retrieve the object label anyway
    return array('objectid' => $objectid,
                 'modid' => $modid,
                 'itemtype' => $itemtype,
                 'itemid' => $itemid);
*/

    $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (!isset($myobject)) return;
    $myobject->getItem();

    // Return the template variables defined in this function
    return array('object' => & $myobject);
}

// ----------------------------------------------------------------------
// TODO: all of the 'standard' user functions, if that makes sense someday...
//

/**
 * generate the common menu configuration
 */
function dynamicdata_user_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();

    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = xarML('Welcome to Dynamic Data');

    // Return the array containing the menu configuration
    return $menu;
}

?>
