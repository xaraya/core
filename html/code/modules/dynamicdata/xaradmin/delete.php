<?php
/**
 * Delete an item
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete item
 * @param array<string, mixed> $args
 * with
 *     'itemid' the id of the item to be deleted
 *     'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete(array $args = [], $context = null)
{
    extract($args);

    if(!xarVar::fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('name', 'isset', $name, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemid', 'int:1:', $itemid, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (empty($itemid)) {
        return xarResponse::notFound();
    }
    if(!xarVar::fetch('confirm', 'isset', $confirm, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('noconfirm', 'isset', $noconfirm, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('join', 'isset', $join, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('tplmodule', 'isset', $tplmodule, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('template', 'isset', $template, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('return_url', 'isset', $return_url, null, xarVar::DONT_SET)) {
        return;
    }

    // set context if available in function
    $myobject = DataObjectFactory::getObject(
        ['objectid' => $objectid,
        'name'       => $name,
        'join'       => $join,
        'table'      => $table,
        'itemid'     => $itemid,
        'tplmodule'  => $tplmodule,
        'template'   => $template],
        $context
    );
    if (empty($myobject)) {
        return;
    }

    // Security
    if (!$myobject->checkAccess('delete')) {
        return xarResponse::Forbidden(xarML('Delete #(1) is forbidden', $myobject->label));
    }

    $data = $myobject->toArray();

    // recover any session var information and remove it from the var
    $data = array_merge($data, xarMod::apiFunc('dynamicdata', 'user', 'getcontext', ['module' => $tplmodule]));
    xarSession::setVar('ddcontext.' . $tplmodule, ['tplmodule' => $tplmodule]);
    extract($data);

    if (!empty($noconfirm)) {
        if (!empty($return_url)) {
            xarController::redirect($return_url);
        } elseif (!empty($table)) {
            xarController::redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'view',
                ['table'     => $table,
                'tplmodule' => $data['tplmodule']]
            ));
        } else {
            xarController::redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'view',
                ['itemid'    => $data['objectid'],
                'tplmodule' => $data['tplmodule']]
            ));
        }
        return true;
    }

    $myobject->getItem();

    if (empty($confirm)) {
        // handle special cases
        if ($myobject->objectid == 1) {
            // check security of the parent object
            // set context if available in function
            $tmpobject = DataObjectFactory::getObject(['objectid' => $myobject->itemid], $context);
            if (!$tmpobject->checkAccess('config')) {
                return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
            }

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
            // set context if available in function
            $tmpobject = DataObjectFactory::getObject(['objectid' => $myobject->properties['objectid']->value], $context);
            if (!$tmpobject->checkAccess('config')) {
                return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $tmpobject->label));
            }
            unset($tmpobject);
        }

        // TODO: is this needed?
        $data = array_merge($data, xarMod::apiFunc('dynamicdata', 'admin', 'menu'));
        $data['object'] = $myobject;
        if ($data['objectid'] == 1) {
            $mylist = DataObjectFactory::getObjectList(['objectid' => $data['itemid']]);
            if (count($mylist->properties) > 0) {
                $data['related'] = xarML('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
            }
        }
        $data['authid'] = xarSec::genAuthKey();

        xarTpl::setPageTitle(xarML('Delete Item #(1) in #(2)', $data['itemid'], $myobject->label));

        if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-delete.xt') ||
            file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-delete-' . $data['template'] . '.xt')) {
            return xarTpl::module($data['tplmodule'], 'admin', 'delete', $data, $data['template']);
        } else {
            return xarTpl::module('dynamicdata', 'admin', 'delete', $data, $data['template']);
        }
    }

    // If we get here it means that the user has confirmed the action

    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges', 'user', 'errors', ['layout' => 'bad_author']);
    }

    // special case for a dynamic object : delete its properties too // TODO: and items
    // TODO: extend to any parent-child relation ?
    if ($data['objectid'] == 1) {
        $mylist = DataObjectFactory::getObjectList(['objectid' => $data['itemid']]);
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = DataPropertyMaster::deleteProperty(['itemid' => $propid]);
        }
    }

    $itemid = $myobject->deleteItem();
    if (!empty($return_url)) {
        xarController::redirect($return_url);
    } elseif (!empty($table)) {
        xarController::redirect(xarController::URL(
            'dynamicdata',
            'admin',
            'view',
            ['table'     => $table,
            'tplmodule' => $tplmodule]
        ));
    } else {
        xarController::redirect(xarController::URL(
            'dynamicdata',
            'admin',
            'view',
            ['name' => $myobject->name,
            'tplmodule' => $tplmodule]
        ));
    }

    return true;
}
