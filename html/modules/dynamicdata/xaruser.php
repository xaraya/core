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

// ----------------------------------------------------------------------
// Hook functions (user GUI)
// ----------------------------------------------------------------------


// TODO: replace this with block/cached variables/special template tag/... ?
//
//       Ideally, people should be able to use the dynamic fields in their
//       module templates as if they were 'normal' fields -> this means
//       adapting the get() function in the user API of the module, perhaps...

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
    if (empty($extrainfo['module']) || !is_array($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name ' . $modname, 'user', 'displayhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!empty($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
// TODO: find some better way to do this !
    } elseif (xarVarIsCached('Hooks.display','itemtype')) {
        $itemtype = xarVarGetCached('Hooks.display','itemtype');
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
    $fields = xarModAPIFunc('dynamicdata','user','getitem',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'itemid' => $itemid));
    if (!isset($fields) || $fields == false || count($fields) == 0) {
        return;
    }

// TODO: use custom template per module + itemtype ?
     return xarTplModule('dynamicdata','user','displayhook',
                         array('fields' => $fields));

}

// ----------------------------------------------------------------------
// TODO: all of the 'standard' user functions, if that makes sense someday...
//

/**
 * the main user function lists the available objects defined in DD
 *
 */
function dynamicdata_user_main()
{
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_OVERVIEW)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $data = dynamicdata_user_menu();

    if (!xarModAPILoad('dynamicdata','user')) return;

    // get items from the objects table
    $objects = xarModAPIFunc('dynamicdata','user','getobjects');

    $data['items'] = array();
    foreach ($objects as $itemid => $object) {
        if ($itemid < 3) continue;
        $modid = $object['fields']['moduleid']['value'];
        $itemtype = $object['fields']['itemtype']['value'];
        $label = $object['fields']['label']['value'];
        $data['items'][] = array(
                                 'link'     => xarModURL('dynamicdata','user','view',
                                                         array('modid' => $modid,'itemtype' => $itemtype)),
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
    $object = xarModAPIFunc('dynamicdata','user','getobject',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['id']['value'];
        $modid = $object['moduleid']['value'];
        $itemtype = $object['itemtype']['value'];
        $label = $object['label']['value'];
        $param = $object['urlparam']['value'];
    } else {
        $objectid = 0;
        $label = xarML('Dynamic Data Objects');
        $param = '';
    }
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_OVERVIEW)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $data = dynamicdata_user_menu();
    $data['objectid'] = $objectid;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['param'] = $param;
    $data['startnum'] = $startnum;
    $data['label'] = xarML('View #(1)',$label);

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
        $label = $object['label']['value'];
    } else {
        $label = xarML('Dynamic Data Object');
    }

    // Return the template variables defined in this function
    return array('module' => $modid,
                 'itemtype' => $itemtype,
                 'itemid' => $itemid,
                 'label' => $label);
}

/**
 * generate the common menu configuration
 */
function dynamicdata_user_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();

    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = xarML('Dynamic Data');

    // Specify the menu items to be used in your blocklayout template
    $menu['menulabel_view'] = xarML('View Items');
    $menu['menulink_view'] = xarModURL('example','user','view');

    // Specify the labels/links for more menu items if relevant
    // $menu['menulabel_other'] = xarML('Some other menu item');
    // $menu['menulink_other'] = xarModURL('example','user','other');
    // ...

    // Note : you could also put all menu items in a $menu['menuitems'] array
    //
    // Initialise the array that will hold the different menu items
    // $menu['menuitems'] = array();
    //
    // Define a menu item
    // $item = array();
    // $item['menulabel'] = _EXAMPLEVIEW;
    // $item['menulink'] = xarModURL('example','user','view');
    //
    // Add it to the array of menu items
    // $menu['menuitems'][] = $item;
    //
    // Add more menu items to the array
    // ...
    //
    // Then you can let the blocklayout template create the different
    // menu items *dynamically*, e.g. by using something like :
    //
    // <xar:loop name="$menuitems">
    //    <td><a href="&xar-var-menulink;">&xar-var-menulabel;</a></td>
    // </xar:loop>
    //
    // in the templates of your module. Or you could even pass an argument
    // to the user_menu() function to turn links on/off automatically
    // depending on which function is currently called...
    //
    // But most people will prefer to specify all this manually in each
    // blocklayout template anyway :-)

    // Return the array containing the menu configuration
    return $menu;
}

//
// TODO: all of the 'standard' user functions, if that makes sense someday...
// ----------------------------------------------------------------------

?>
