<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Modify an item
 *
 * This is a standard function that is called whenever an administrator
 * wishes to modify a current module item
 *
 * @param int objectid the id of the item to be modified
 * @param int module_id the id of the module where the item comes from
 * @param int itemtype the id of the itemtype of the item
 * @param join
 * @param table
 * @return
 */
function dynamicdata_admin_modify($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'id',    $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id','isset', $module_id,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('notfresh', 'isset', $notfresh,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, NULL, XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('itemid',   'isset', $itemid)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,     NULL, XARVAR_DONT_SET)) {return;}


    $data = xarMod::apiFunc('dynamicdata','admin','menu');

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name' => $name,
                                         'moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule));
    $args = $myobject->toArray();

    // Security check
    if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',$args['moduleid'].":".$args['itemtype'].":".$args['itemid'])) return;

    if ($notfresh) {
        $isvalid = $myobject->checkInput();
    } else {
        $myobject->getItem();
    }
    $data['object'] = & $myobject;

    // if we're editing a dynamic property, save its property type to cache
    // for correct processing of the configuration rule (ValidationProperty)
    if ($myobject->objectid == 2) {
        xarVarSetCached('dynamicdata','currentproptype', $myobject->properties['type']);
    }

    // if we're editing a dynamic object, check its own visibility
    if ($myobject->objectid == 1 && $myobject->itemid > 3) {
        // CHECKME: do we always need to load the object class to get its visibility ?
        $tmpobject = DataObjectMaster::getObject(array('objectid' => $myobject->itemid));
        // override the default visibility and moduleid
        $myobject->visibility = $tmpobject->visibility;
        $myobject->moduleid = $tmpobject->moduleid;
        unset($tmpobject);
    }

    $data['objectid'] = $args['objectid'];
    $data['itemid'] = $args['itemid'];
    $data['authid'] = xarSecGenAuthKey();
    $data['preview'] = $preview;
    $data['tplmodule'] = $args['tplmodule'];   //TODO: is this needed

    // $modinfo = xarMod::getInfo($args['moduleid']);
    // Makes this hooks call explictly from DD
    $modinfo = xarMod::getInfo(182);
    $item = array();
    foreach (array_keys($myobject->properties) as $name) {
        $item[$name] = $myobject->properties[$name]->value;
    }
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $args['itemtype'];
    $item['itemid'] = $args['itemid'];
    $hooks = array();
    $hooks = xarModCallHooks('item', 'modify', $args['itemid'], $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    xarTplSetPageTitle(xarML('Modify Item #(1) in #(2)', $data['itemid'], $myobject->label));

    if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify.xt') ||
        file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify-' . $args['template'] . '.xt')) {
        return xarTplModule($args['tplmodule'],'admin','modify',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','admin','modify',$data,$args['template']);
    }
}

?>
