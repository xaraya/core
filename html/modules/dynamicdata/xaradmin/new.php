<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Show add new item form
 *
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 */
function dynamicdata_admin_new($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'id', $objectid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'id', $modid,        182,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'id', $itemtype,     0,    XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    0,    XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('notfresh', 'isset', $notfresh,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','str',   $tplmodule, NULL, XARVAR_DONT_SET)) {return;}

    if(!xarSecurityCheck('AddDynamicDataItem',1,'Item',"$modid:$itemtype:All")) return;

    $data = xarModAPIFunc('dynamicdata','admin','menu');

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name'      => $name,
                                         'moduleid'  => $modid,
                                         'itemtype'  => $itemtype,
                                         'join'      => $join,
                                         'table'     => $table,
                                         'itemid'    => $itemid,
                                         'tplmodule' => $tplmodule,
                                         'template'  => $template,
                                         ));

    $args = $myobject->toArray();
    if (isset($myobject->properties['module_id'])) {
        if ($notfresh) $isvalid = $myobject->checkInput();
        $itemtype = xarModAPIFunc('dynamicdata','admin', 'getnextitemtype', array('modid' => $myobject->properties['module_id']->value));
        $myobject->properties['itemtype']->value = $itemtype;
    }
    $data['object'] =& $myobject;
    $data['tplmodule'] = $args['tplmodule'];  //TODO: is this needed?

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    // Makes this hooks call explictly from DD
    //$modinfo = xarModGetInfo($myobject->moduleid);
    $modinfo = xarModGetInfo(182);
    $item = array();
    foreach (array_keys($myobject->properties) as $name) {
        $item[$name] = $myobject->properties[$name]->value;
    }
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $myobject->itemtype;
    $item['itemid'] = $myobject->itemid;
    $hooks = array();
    $hooks = xarModCallHooks('item', 'new', $myobject->itemid, $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    if (file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-new.xd') ||
        file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-new-' . $args['template'] . '.xd')) {
        return xarTplModule($args['tplmodule'],'admin','new',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','admin','new',$data,$args['template']);
    }
}
?>
