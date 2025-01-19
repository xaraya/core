<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\DataObject\AdminGui;
use DataObjectFactory;
use DataPropertyMaster;
use xarController;
use xarMod;
use xarSec;
use xarSession;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin delete function
 * @extends MethodClass<AdminGui>
 */
class DeleteMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * delete item
     * @param array<string,mixed> $args
     * with
     *     'itemid' the id of the item to be deleted
     *     'confirm' confirm that this item can be deleted
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->var()->fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('name', 'isset', $name, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('itemid', 'int:1:', $itemid, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (empty($itemid)) {
            $msg = $this->ml('Data object not found');
            return $this->ctl()->notFound($msg);
        }
        if (!$this->var()->fetch('confirm', 'isset', $confirm, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('noconfirm', 'isset', $noconfirm, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('join', 'isset', $join, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('tplmodule', 'isset', $tplmodule, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('template', 'isset', $template, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('return_url', 'isset', $return_url, null, xarVar::DONT_SET)) {
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
            $this->getContext()
        );
        if (empty($myobject)) {
            return;
        }

        // Security
        if (!$myobject->checkAccess('delete')) {
            $msg = $this->ml('Delete #(1) is forbidden', $myobject->label);
            return $this->ctl()->forbidden($msg);
        }

        $data = $myobject->toArray();

        // recover any session var information and remove it from the var
        $data = array_merge($data, xarMod::apiFunc('dynamicdata', 'user', 'getcontext', ['module' => $tplmodule]));
        xarSession::setVar('ddcontext.' . $tplmodule, ['tplmodule' => $tplmodule]);
        extract($data);

        if (!empty($noconfirm)) {
            if (!empty($return_url)) {
                $this->ctl()->redirect($return_url);
            } elseif (!empty($table)) {
                $this->ctl()->redirect(xarController::URL(
                    'dynamicdata',
                    'admin',
                    'view',
                    ['table'     => $table,
                        'tplmodule' => $data['tplmodule']]
                ));
            } else {
                $this->ctl()->redirect(xarController::URL(
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
                $tmpobject = DataObjectFactory::getObject(['objectid' => $myobject->itemid], $this->getContext());
                if (!$tmpobject->checkAccess('config')) {
                    $msg = $this->ml('Configure #(1) is forbidden', $tmpobject->label);
                    return $this->ctl()->forbidden($msg);
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
                $tmpobject = DataObjectFactory::getObject(['objectid' => $myobject->properties['objectid']->value], $this->getContext());
                if (!$tmpobject->checkAccess('config')) {
                    $msg = $this->ml('Configure #(1) is forbidden', $tmpobject->label);
                    return $this->ctl()->forbidden($msg);
                }
                unset($tmpobject);
            }

            // TODO: is this needed?
            $data = array_merge($data, xarMod::apiFunc('dynamicdata', 'admin', 'menu'));
            $data['object'] = $myobject;
            if ($data['objectid'] == 1) {
                $mylist = DataObjectFactory::getObjectList(['objectid' => $data['itemid']]);
                if (count($mylist->properties) > 0) {
                    $data['related'] = $this->ml('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
                }
            }
            $data['authid'] = $this->sec()->genAuthKey();
            $data['context'] ??= $myobject->getContext();

            xarTpl::setPageTitle($this->ml('Delete Item #(1) in #(2)', $data['itemid'], $myobject->label));

            if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-delete.xt') ||
                file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-delete-' . $data['template'] . '.xt')) {
                return xarTpl::module($data['tplmodule'], 'admin', 'delete', $data, $data['template']);
            } else {
                return xarTpl::module('dynamicdata', 'admin', 'delete', $data, $data['template']);
            }
        }

        // If we get here it means that the user has confirmed the action

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
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
            $this->ctl()->redirect($return_url);
        } elseif (!empty($table)) {
            $this->ctl()->redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'view',
                ['table'     => $table,
                    'tplmodule' => $tplmodule]
            ));
        } else {
            $this->ctl()->redirect(xarController::URL(
                'dynamicdata',
                'admin',
                'view',
                ['name' => $myobject->name,
                    'tplmodule' => $tplmodule]
            ));
        }

        return true;
    }
}
