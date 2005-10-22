<?php
/**
 * Update current item
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Update current item
 * This is a standard function that is called with the results of the
 * form supplied by xarModFunc('dynamicdata','admin','modify') to update a current item
 * @param 'exid' the id of the item to be updated
 * @param 'name' the name of the item to be updated
 * @param 'number' the number of the item to be updated
 */
function dynamicdata_admin_update($args)
{
    extract($args);

    if(!xarVarFetch('objectid',   'isset', $objectid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',      'isset', $modid,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype',   'isset', $itemtype,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'isset', $itemid,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',    'isset', $preview,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',       'isset', $join,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',      'isset', $table,       NULL, XARVAR_DONT_SET)) {return;}

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

    $myobject = & Dynamic_Object_Master::getObject(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    $myobject->getItem();

    // if we're editing a dynamic property, save its property type to cache
    // for correct processing of the validation rule (Dynamic_Validation_Property)
    if ($myobject->objectid == 2) {
        xarVarSetCached('dynamicdata','currentproptype', $myobject->properties['type']);
    }

    $isvalid = $myobject->checkInput();

    if (!empty($preview) || !$isvalid) {
        $data = xarModAPIFunc('dynamicdata','admin','menu');
        $data['object'] = & $myobject;

        $data['objectid'] = $myobject->objectid;
        $data['itemid'] = $itemid;
        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;

        $modinfo = xarModGetInfo($myobject->moduleid);
        $item = array();
        foreach (array_keys($myobject->properties) as $name) {
            $item[$name] = $myobject->properties[$name]->value;
        }
        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $myobject->itemtype;
        $item['itemid'] = $myobject->itemid;
        $hooks = array();
        $hooks = xarModCallHooks('item', 'modify', $myobject->itemid, $item, $modinfo['name']); 
        $data['hooks'] = $hooks;
 
        return xarTplModule('dynamicdata','admin','modify', $data);
    }

    $itemid = $myobject->updateItem();

    if (!isset($itemid)) return; // throw back

    // special case for dynamic objects themselves
    if ($myobject->objectid == 1) {
        // check if we need to set a module alias (or remove it) for short URLs
        $name = $myobject->properties['name']->value;
        $alias = xarModGetAlias($name);
        $isalias = $myobject->properties['isalias']->value;
        if (!empty($isalias)) {
            // no alias defined yet, so we create one
            if ($alias == $name) {
                $args = array('modName'=>'dynamicdata', 'aliasModName'=> $name);
                xarModAPIFunc('modules', 'admin', 'add_module_alias', $args);
            }
        } else {
            // this was a defined alias, so we remove it
            if ($alias == 'dynamicdata') {
                $args = array('modName'=>'dynamicdata', 'aliasModName'=> $name);
                xarModAPIFunc('modules', 'admin', 'delete_module_alias', $args);
            }
        }

        // resynchronise properties with object in terms of module id and itemtype (for now)
        $objectid = $myobject->properties['objectid']->value;
        $moduleid = $myobject->properties['moduleid']->value;
        $itemtype = $myobject->properties['itemtype']->value;
        if (!xarModAPIFunc('dynamicdata','admin','syncprops',
                           array('objectid' => $objectid,
                                 'moduleid' => $moduleid,
                                 'itemtype' => $itemtype))) {
            return;
        }
    }

    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } elseif ($myobject->objectid == 2) { // for dynamic properties, return to modifyprop
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                      array('itemid' => $myobject->properties['objectid']->value)));
    } elseif (!empty($table)) {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('itemid' => $myobject->objectid)));
    }

    // Return
    return true;
}


?>
