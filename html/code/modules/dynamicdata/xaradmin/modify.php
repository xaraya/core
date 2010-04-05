<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
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
 * @return string
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
    if (!xarVarFetch('tab', 'pre:trim:lower:str:1', $data['tab'], 'edit', XARVAR_NOT_REQUIRED)) return;

    $object = DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name' => $name,
                                         'moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule));
    $args = $object->toArray();

    if ($notfresh) {
        $isvalid = $object->checkInput();
    } else {
        $object->getItem();
    }
    $data['object'] = $object;

    switch ($data['tab']) {

        case 'edit':

            // Security check
            if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',$args['moduleid'].":".$args['itemtype'].":".$args['itemid'])) return;

            // if we're editing a dynamic property, save its property type to cache
            // for correct processing of the configuration rule (ValidationProperty)
            if ($object->objectid == 2) {
                xarVarSetCached('dynamicdata','currentproptype', $object->properties['type']);
            }

            // if we're editing a dynamic object, check its own visibility
            if ($object->objectid == 1 && $object->itemid > 3) {
                // CHECKME: do we always need to load the object class to get its visibility ?
                $tmpobject = DataObjectMaster::getObject(array('objectid' => $object->itemid));
                // override the default visibility and moduleid
                $object->visibility = $tmpobject->visibility;
                $object->moduleid = $tmpobject->moduleid;
                unset($tmpobject);
            }

            $data['itemid'] = $args['itemid'];
            $data['preview'] = $preview;

            // Makes this hooks call explictly from DD - why ???
            ////$modinfo = xarMod::getInfo($args['moduleid']);
            //$modinfo = xarMod::getInfo(182);
            $object->callHooks('modify');
            $data['hooks'] = $object->hookoutput;

            xarTplSetPageTitle(xarML('Modify Item #(1) in #(2)', $data['itemid'], $object->label));

        break;

        case 'clone':
            // user needs admin access to changethe access rules
            $data['adminaccess'] = xarSecurityCheck('',0,'All',$object->objectid . ":" . $name . ":" . "$itemid",0,'',0,800);
            $data['name'] = $object->properties['name']->value;
        break;
    }
    
    $data['tplmodule'] = $args['tplmodule'];   //TODO: is this needed
    $data['objectid'] = $args['objectid'];
    $data['authid'] = xarSecGenAuthKey();
            
    if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify.xt') ||
        file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify-' . $args['template'] . '.xt')) {
        return xarTplModule($args['tplmodule'],'admin','modify',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','admin','modify',$data,$args['template']);
    }
}

?>
