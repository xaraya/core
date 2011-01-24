<?php
/**
 * Delete an item
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
 * delete item
 * @param 'itemid' the id of the item to be deleted
 * @param 'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete(Array $args=array())
{
   extract($args);

    if(!xarVarFetch('objectid',   'isset', $objectid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',       'isset', $name,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'int:1:',    $itemid, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (empty($itemid)) return xarResponse::notFound();
    if(!xarVarFetch('confirm',    'isset', $confirm,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('noconfirm',  'isset', $noconfirm,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',       'isset', $join,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',      'isset', $table,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule',  'isset', $tplmodule,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template',   'isset', $template,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name'       => $name,
                                         'join'       => $join,
                                         'table'      => $table,
                                         'itemid'     => $itemid,
                                         'tplmodule'  => $tplmodule,
                                         'template'   => $template));
    if (empty($myobject)) return;
    
    // Security
    if (!$myobject->checkAccess('delete'))
        return xarResponse::Forbidden(xarML('Delete #(1) is forbidden', $myobject->label));

    $data = $myobject->toArray();

    // recover any session var information and remove it from the var
    $data = array_merge($data,xarMod::apiFunc('dynamicdata','user','getcontext',array('module' => $tplmodule)));
    xarSession::setVar('ddcontext.' . $tplmodule, array('tplmodule' => $tplmodule));
    extract($data);

    if (!empty($noconfirm)) {
        if (!empty($return_url)) {
            xarController::redirect($return_url);
        } elseif (!empty($table)) {
            xarController::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array(
                                            'table'     => $table,
                                            'tplmodule' => $data['tplmodule'],
                                          )));
        } else {
            xarController::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array(
                                            'itemid'    => $data['objectid'],
                                            'tplmodule' => $data['tplmodule'],
                                          )));
        }
        return true;
    }

    $myobject->getItem();

    if (empty($confirm)) {
        // handle special cases
        if ($myobject->objectid == 1) {
            // check security of the parent object
            $tmpobject = DataObjectMaster::getObject(array('objectid' => $myobject->itemid));
            if (!$tmpobject->checkAccess('config'))
                return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));

            // if we're editing a dynamic object, check its own visibility
            if ($myobject->itemid > 3) {
                // CHECKME: do we always need to load the object class to get its visibility ?
                // override the default visibility and moduleid
                $myobject->visibility = $tmpobject->visibility;
                $myobject->moduleid = $tmpobject->moduleid;
            }
            unset($tmpobject);

        } elseif ($myobject->objectid == 2) {
            // check security of the parent object
            $tmpobject = DataObjectMaster::getObject(array('objectid' => $myobject->properties['objectid']->value));
            if (!$tmpobject->checkAccess('config'))
                return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
            unset($tmpobject);
        }

        // TODO: is this needed?
        $data = array_merge($data,xarMod::apiFunc('dynamicdata','admin','menu'));
        $data['object'] = $myobject;
        if ($data['objectid'] == 1) {
            $mylist = DataObjectMaster::getObjectList(array('objectid' => $data['itemid']));
            if (count($mylist->properties) > 0) {
                $data['related'] = xarML('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
            }
        }
        $data['authid'] = xarSecGenAuthKey();

        xarTpl::setPageTitle(xarML('Delete Item #(1) in #(2)', $data['itemid'], $myobject->label));

        if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-delete.xt') ||
            file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-delete-' . $data['template'] . '.xt')) {
            return xarTpl::module($data['tplmodule'],'admin','delete',$data,$data['template']);
        } else {
            return xarTpl::module('dynamicdata','admin','delete',$data,$data['template']);
        }
    }

    // If we get here it means that the user has confirmed the action

    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // special case for a dynamic object : delete its properties too // TODO: and items
// TODO: extend to any parent-child relation ?
    if ($data['objectid'] == 1) {
        $mylist = & DataObjectMaster::getObjectList(array('objectid' => $data['itemid']));
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = DataPropertyMaster::deleteProperty(array('itemid' => $propid));
        }
    }

    $itemid = $myobject->deleteItem();
    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } elseif (!empty($table)) {
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array(
                                      'table'     => $table,
                                      'tplmodule' => $tplmodule,
                                      )));
    } else {
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array(
                                      'name' => $myobject->name,
                                      'tplmodule' => $tplmodule,
                                      )));
    }

    return true;
}

?>
