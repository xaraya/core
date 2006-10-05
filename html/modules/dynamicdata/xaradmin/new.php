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
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 */
function dynamicdata_admin_new($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     182,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  0,    XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    0,    XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('notfresh', 'isset', $notfresh,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','str',   $tplmodule, 'dynamicdata', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}

    if($modid == 182) {
        // Dynamicdata module is special
        $ancestor = array('objectid' => $objectid, 'modid' => $modid, 'itemtype' => $itemtype);
    } else {
        if (isset($objectid)) {
            $ancestor = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('objectid' => $objectid));
        } else {
            $ancestor = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('moduleid' => $modid,'itemtype' => $itemtype));
        }
    }
    $itemtype = $ancestor['itemtype'];

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AddDynamicDataItem',1,'Item',"$modid:$itemtype:All")) return;

    $data = xarModAPIFunc('dynamicdata','admin','menu');

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule));

    if (isset($myobject->properties['moduleid'])) {
        if ($notfresh) $isvalid = $myobject->checkInput();
        $itemtype = xarModAPIFunc('dynamicdata','admin', 'getnextitemtype', array('modid' => $myobject->properties['moduleid']->value));
        $myobject->properties['itemtype']->value = $itemtype;
    }
    $data['object'] =& $myobject;
    $data['tplmodule'] = $tplmodule;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    $modinfo = xarModGetInfo($myobject->moduleid);
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

    if(!isset($template)) {
        $template = $myobject->name;
    }
    return xarTplModule($tplmodule,'admin','new',$data,$template);
}

?>