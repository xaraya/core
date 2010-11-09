<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * view a list of items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 *
 * @return array
 */
function dynamicdata_user_view($args)
{
    // Old-style arguments
    if(!xarVarFetch('objectid', 'int',   $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id','int',   $module_id, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('moduleid', 'int',   $moduleid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'int',   $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    // New-style arguments
    if(!xarVarFetch('itemid',   'int',   $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('startnum', 'int',   $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'int',   $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',    'isset', $catid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1' ,$layout,    'default', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, 'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}

    // Override if needed from argument array
    extract($args);

    // Support old-style arguments
    if (empty($itemid) && !empty($objectid)) {
        $itemid = $objectid;
    }
    if (empty($module_id) && !empty($moduleid)) {
        $module_id = $moduleid;
    }
    if (empty($module_id)) {
        $module_id = xarMod::getRegID('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    // Default number of items per page in user view
    if (empty($numitems)) {
        $numitems = xarModVars::get('dynamicdata', 'items_per_page');
    }

    // Note: we need to pass all relevant arguments ourselves here
    $object = DataObjectMaster::getObjectList(
                            array('objectid'  => $itemid,
                                  'moduleid'  => $module_id,
                                  'itemtype'  => $itemtype,
                                  'name'      => $name,
                                  'startnum'  => $startnum,
                                  'numitems'  => $numitems,
                                  'sort'      => $sort,
                                  'join'      => $join,
                                  'table'     => $table,
                                  'catid'     => $catid,
                                  'layout'    => $layout,
                                  'tplmodule' => $tplmodule,
                                  'template'  => $template,
                                  ));

    if (!$object->checkAccess('view'))
        return xarResponse::Forbidden(xarML('View #(1) is forbidden', $object->label));

    // Pass back the relevant variables to the template if necessary
    $data = $object->toArray();

    // Count the number of items matching the preset arguments - do this before getItems()
    $object->countItems();

    // Get the selected items using the preset arguments
    $object->getItems();

    // Pass the object list to the template
    $data['object'] = $object;

    // TODO: is this needed?
    $data = array_merge($data,xarMod::apiFunc('dynamicdata','admin','menu'));
    // TODO: remove this when we turn all the moduleid into module_id
    $data['module_id'] = $data['moduleid'];
    // TODO: another stray
    $data['catid'] = $catid;

    xarTplSetPageTitle(xarML('View #(1)', $object->label));

    if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/user-view.xt') ||
        file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/user-view-' . $data['template'] . '.xt')) {
        return xarTplModule($data['tplmodule'],'user','view',$data,$data['template']);
    } else {
        return xarTplModule('dynamicdata','user','view',$data,$args['template']);
    }
}

?>