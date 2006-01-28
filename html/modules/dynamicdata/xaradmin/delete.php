<?php
/**
 * Delete an item
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
 * delete item
 * @param 'itemid' the id of the item to be deleted
 * @param 'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete($args)
{
   extract($args);
 
    if(!xarVarFetch('objectid', 'isset', $objectid, NULL,                               XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'id',    $modid,    xarModGetIDFromName('dynamicdata'), XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'int',   $itemtype, 0,                                  XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemid',   'id',    $itemid                                                           )) {return;}
    if(!xarVarFetch('confirm',  'isset', $confirm,  NULL,                               XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('noconfirm','isset', $noconfirm, NULL,                              XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}

    $myobject = & Dynamic_Object_Master::getObject(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    if (!empty($noconfirm)) {
        if (!empty($table)) {
            xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array('table' => $table)));
        } else {
            xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array('itemid' => $objectid)));
        }
        return true;
    }

    $myobject->getItem();

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if(!xarSecurityCheck('DeleteDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    if (empty($confirm)) {
        $data = xarModAPIFunc('dynamicdata','admin','menu');
        $data['object'] = & $myobject;
        if ($myobject->objectid == 1) {
            $mylist = & Dynamic_Object_Master::getObjectList(array('objectid' => $itemid));
            if (count($mylist->properties) > 0) {
                $data['related'] = xarML('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
            }
        }
        $data['authid'] = xarSecGenAuthKey();

        if(!isset($template)) {
            $template = $myobject->name;
        }
        return xarTplModule('dynamicdata','admin','delete',$data,$template);
    }

    // If we get here it means that the user has confirmed the action

    if (!xarSecConfirmAuthKey()) return;

    // special case for a dynamic object : delete its properties too // TODO: and items
// TODO: extend to any parent-child relation ?
    if ($myobject->objectid == 1) {
        $mylist = & Dynamic_Object_Master::getObjectList(array('objectid' => $itemid));
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = Dynamic_Property_Master::deleteProperty(array('itemid' => $propid));
        }
    }

    $itemid = $myobject->deleteItem();

    if (!empty($table)) {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('itemid' => $objectid)));
    }

    // Return
    return true;

}

?>