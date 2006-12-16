<?php
/**
 * Delete an item
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
 * delete item
 * @param 'itemid' the id of the item to be deleted
 * @param 'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete($args)
{
   extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $moduleid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'id',    $itemid                          )) {return;}
    if(!xarVarFetch('confirm',  'isset', $confirm,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('noconfirm','isset', $noconfirm, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name' => $name,
                                         'moduleid' => $moduleid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid,
                                         'extend'   => false));  //Note: this means we only delete this extension, not the parent
    if (empty($myobject)) return;
    $args = $myobject->toArray();

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if(!xarSecurityCheck('DeleteDynamicDataItem',1,'Item',$args['moduleid'].":".$args['itemtype'].":".$args['itemid'])) return;

    if (!empty($noconfirm)) {
        if (!empty($table)) {
            xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array('table' => $table)));
        } else {
            xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array('itemid' => $args['objectid'])));
        }
        return true;
    }

    $myobject->getItem();


    if (empty($confirm)) {
        $data = xarModAPIFunc('dynamicdata','admin','menu');
        $data['object'] = & $myobject;
        if ($args['objectid'] == 1) {
            $mylist = & DataObjectMaster::getObjectList(array('objectid' => $args['itemid'], 'extend' => false));
            if (count($mylist->properties) > 0) {
                $data['related'] = xarML('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
            }
        }
        $data['authid'] = xarSecGenAuthKey();

        if (file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-delete.xd') ||
            file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-delete-' . $args['template'] . '.xd')) {
            return xarTplModule($args['tplmodule'],'admin','delete',$data,$args['template']);
        } else {
            return xarTplModule('dynamicdata','admin','delete',$data,$args['template']);
        }
    }

    // If we get here it means that the user has confirmed the action

    if (!xarSecConfirmAuthKey()) return;

    // special case for a dynamic object : delete its properties too // TODO: and items
// TODO: extend to any parent-child relation ?
    if ($args['objectid'] == 1) {
        $mylist = & DataObjectMaster::getObjectList(array('objectid' => $args['itemid'], 'extend' => false));
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = DataPropertyMaster::deleteProperty(array('itemid' => $propid));
        }
    }

    $itemid = $myobject->deleteItem();

    if (!empty($table)) {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('itemid' => $args['objectid'])));
    }

    // Return
    return true;

}

?>
