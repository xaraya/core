<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
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

    if (empty($objectid) && empty($name)) $objectid = 1;
    $object = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name' => $name,
                                         'moduleid' => $module_id,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule));
    if (empty($object)) return;
    if (!$object->checkAccess('update'))
        return xarResponse::Forbidden(xarML('Update #(1) is forbidden', $object->label));

    $args = $object->toArray();

    if ($notfresh) {
        $isvalid = $object->checkInput();
    } else {
        $object->getItem();
    }
    $data['object'] = $object;
    $data['itemid'] = $args['itemid'];

    switch ($data['tab']) {

        case 'edit':

            // handle special cases
            if ($object->objectid == 1) {
                // check security of the parent object
                $tmpobject = DataObjectMaster::getObject(array('objectid' => $object->itemid));
                if (!$tmpobject->checkAccess('config'))
                    return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));

                // if we're editing a dynamic object, check its own visibility
                if ($object->itemid > 3) {
                    // CHECKME: do we always need to load the object class to get its visibility ?
                    // override the default visibility and moduleid
                    $object->visibility = $tmpobject->visibility;
                    $object->moduleid = $tmpobject->moduleid;
                }
                unset($tmpobject);

            } elseif ($object->objectid == 2) {
                // check security of the parent object
                $tmpobject = DataObjectMaster::getObject(array('objectid' => $object->properties['objectid']->value));
                if (!$tmpobject->checkAccess('config'))
                    return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
                unset($tmpobject);

                // if we're editing a dynamic property, save its property type to cache
                // for correct processing of the configuration rule (ValidationProperty)
                xarVarSetCached('dynamicdata','currentproptype', $object->properties['type']);
            }

            $data['preview'] = $preview;

            // Makes this hooks call explictly from DD - why ???
            ////$modinfo = xarMod::getInfo($args['moduleid']);
            //$modinfo = xarMod::getInfo(182);
            $object->callHooks('modify');
            $data['hooks'] = $object->hookoutput;

            if ($object->objectid == 1) {
                $data['label'] = $object->properties['label']->value;
                xarTplSetPageTitle(xarML('Modify DataObject #(1)', $data['label']));
            } else {
                $data['label'] = $object->label;
                xarTplSetPageTitle(xarML('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
            }

        break;

        case 'clone':
            // user needs admin access to changethe access rules
            $data['adminaccess'] = xarSecurityCheck('',0,'All',$object->objectid . ":" . $name . ":" . "$itemid",0,'',0,800);
            $data['name'] = $object->properties['name']->value;
            if ($object->objectid == 1) {
                $data['label'] = $object->properties['label']->value;
                xarTplSetPageTitle(xarML('Clone DataObject #(1)', $data['label']));
            } else {
                $data['label'] = $object->label;
                xarTplSetPageTitle(xarML('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
            }
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
