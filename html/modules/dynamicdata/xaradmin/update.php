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
 * Update current item
 *
 * This is a standard function that is called with the results of the
 * form supplied by xarModFunc('dynamicdata','admin','modify') to update a current item
 *
 * @param int    objectid
 * @param int    modid
 * @param int    itemtype
 * @param int    itemid
 * @param string return_url
 * @param bool   preview
 * @param string join
 * @param string table
 */
function dynamicdata_admin_update($args)
{
    extract($args);

    if(!xarVarFetch('objectid',   'isset', $objectid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',      'int',   $modid,       xarModGetIDFromName('dynamicdata'), XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype',   'int',   $itemtype,    0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemid',     'isset', $itemid,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',    'isset', $preview,     0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('join',       'isset', $join,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',      'isset', $table,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule',  'isset', $tplmodule,   'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}

    if (!xarSecConfirmAuthKey()) return;
    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    $itemid = $myobject->getItem();
    // if we're editing a dynamic property, save its property type to cache
    // for correct processing of the validation rule (ValidationProperty)
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
        $data['tplmodule'] = $tplmodule;
        if (!empty($return_url)) {
            $data['return_url'] = $return_url;
        }

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
    // Valid and not previewing, update the object
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

    }

    if (!empty($return_url)) {
        if (strpos($return_url,'?') === false)
            $return_url .= '?';
        else
            $return_url .= '&';
        $return_url .= 'itemid=' . $itemid;
        xarResponseRedirect($return_url);
    } elseif ($myobject->objectid == 2) { // for dynamic properties, return to modifyprop
        $objectid = $myobject->properties['objectid']->value;
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                      array('itemid' => $objectid)));
    } elseif (!empty($table)) {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array(
                                      'itemid' => $objectid,
                                      'tplmodule' => $tplmodule
                                      )));
    }
    return true;
}


?>
