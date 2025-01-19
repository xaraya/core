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
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin modify function
 * @extends MethodClass<AdminGui>
 */
class ModifyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify an item
     * This is a standard function that is called whenever an administrator
     * wishes to modify a current module item
     * @param array<string,mixed> $args
     * with
     *     int objectid the id of the item to be modified
     *     int module_id the id of the module where the item comes from
     *     int itemtype the id of the itemtype of the item
     *     string join
     *     string table
     * @return string|void output display string
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->var()->fetch('objectid', 'id', $objectid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('name', 'isset', $name, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('join', 'isset', $join, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('notfresh', 'isset', $notfresh, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('tplmodule', 'isset', $tplmodule, null, xarVar::DONT_SET)) {
            return;
        }

        if (!$this->var()->fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('template', 'isset', $template, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->var()->fetch('preview', 'isset', $preview, null, xarVar::DONT_SET)) {
            return;
        }

        $data = xarMod::apiFunc('dynamicdata', 'admin', 'menu');
        if (!$this->var()->fetch('tab', 'pre:trim:lower:str:1', $data['tab'], 'edit', xarVar::NOT_REQUIRED)) {
            return;
        }

        if (empty($objectid) && empty($name)) {
            $objectid = 1;
        }
        // set context if available in function
        $object = DataObjectFactory::getObject(
            ['objectid' => $objectid,
                'name' => $name,
                'moduleid' => $module_id,
                'itemtype' => $itemtype,
                'join'     => $join,
                'table'    => $table,
                'itemid'   => $itemid,
                'tplmodule' => $tplmodule],
            $this->getContext()
        );

        // Security
        if (empty($object) || empty($itemid)) {
            $msg = $this->ml('Data object not found');
            return $this->ctl()->notFound($msg);
        }
        if (!$object->checkAccess('update')) {
            $msg = $this->ml('Update #(1) is forbidden', $object->label);
            return $this->ctl()->forbidden($msg);
        }

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
                    // set context if available in function
                    $tmpobject = DataObjectFactory::getObject(['objectid' => $object->itemid], $this->getContext());
                    if (empty($tmpobject)) {
                        $msg = $this->ml('Data object not found');
                        return $this->ctl()->notFound($msg);
                    }
                    if (!$tmpobject->checkAccess('config')) {
                        $msg = $this->ml('Configure #(1) is forbidden', $tmpobject->label);
                        return $this->ctl()->forbidden($msg);
                    }

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
                    // set context if available in function
                    $tmpobject = DataObjectFactory::getObject(['objectid' => $object->properties['objectid']->value], $this->getContext());
                    if (!$tmpobject->checkAccess('config')) {
                        $msg = $this->ml('Configure #(1) is forbidden', $tmpobject->label);
                        return $this->ctl()->forbidden($msg);
                    }
                    unset($tmpobject);

                    // if we're editing a dynamic property, save its property type to cache
                    // for correct processing of the configuration rule (ValidationProperty)
                    xarVar::setCached('dynamicdata', 'currentproptype', $object->properties['type']);
                }

                $data['preview'] = $preview;

                // Makes this hooks call explictly from DD - why ???
                ////$modinfo = xarMod::getInfo($args['moduleid']);
                //$modinfo = xarMod::getInfo(182);
                $object->callHooks('modify');
                $data['hooks'] = $object->hookoutput;

                if ($object->objectid == 1) {
                    $data['label'] = $object->properties['label']->value;
                    xarTpl::setPageTitle($this->ml('Modify DataObject #(1)', $data['label']));
                } else {
                    $data['label'] = $object->label;
                    xarTpl::setPageTitle($this->ml('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
                }

                break;

            case 'clone':
                // user needs admin access to change the access rules
                $data['adminaccess'] = xarSecurity::check('', 0, 'All', $object->objectid . ":" . $name . ":" . "$itemid", '', '', 0, 800);
                $data['name'] = $object->properties['name']->value;
                if ($object->objectid == 1) {
                    $data['label'] = $object->properties['label']->value;
                    xarTpl::setPageTitle($this->ml('Clone DataObject #(1)', $data['label']));
                } else {
                    $data['label'] = $object->label;
                    xarTpl::setPageTitle($this->ml('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
                }
                break;
        }

        $data['tplmodule'] = $args['tplmodule'];   //TODO: is this needed
        $data['objectid'] = $args['objectid'];
        $data['authid'] = $this->sec()->genAuthKey();
        $data['context'] = $object->getContext();

        if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify.xt') ||
            file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify-' . $args['template'] . '.xt')) {
            return xarTpl::module($args['tplmodule'], 'admin', 'modify', $data, $args['template']);
        } else {
            return xarTpl::module('dynamicdata', 'admin', 'modify', $data, $args['template']);
        }
    }
}
