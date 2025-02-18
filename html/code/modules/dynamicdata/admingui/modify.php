<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\AdminApi;
use DataObjectFactory;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('modules.dynamicdata.method');


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
     * @see AdminGui::modify()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!$this->var()->check('objectid', $objectid, 'id')) {
            return;
        }
        if (!$this->var()->check('name', $name)) {
            return;
        }
        if (!$this->var()->check('module_id', $module_id)) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype)) {
            return;
        }
        if (!$this->var()->check('join', $join)) {
            return;
        }
        if (!$this->var()->check('table', $table)) {
            return;
        }
        if (!$this->var()->check('notfresh', $notfresh)) {
            return;
        }
        if (!$this->var()->check('tplmodule', $tplmodule)) {
            return;
        }

        if (!$this->var()->check('itemid', $itemid)) {
            return;
        }
        if (!$this->var()->check('template', $template)) {
            return;
        }
        if (!$this->var()->check('preview', $preview)) {
            return;
        }

        $data = $adminapi->menu();
        if (!$this->var()->find('tab', $data['tab'], 'pre:trim:lower:str:1', 'edit')) {
            return;
        }

        if (empty($objectid) && empty($name)) {
            $objectid = 1;
        }
        // set context if available in function
        $object = $this->data()->getObject(
            ['objectid' => $objectid,
                'name' => $name,
                'moduleid' => $module_id,
                'itemtype' => $itemtype,
                'join'     => $join,
                'table'    => $table,
                'itemid'   => $itemid,
                'tplmodule' => $tplmodule]
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
                    $tmpobject = $this->data()->getObject(['objectid' => $object->itemid]);
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
                    $tmpobject = $this->data()->getObject(['objectid' => $object->properties['objectid']->value]);
                    if (!$tmpobject->checkAccess('config')) {
                        $msg = $this->ml('Configure #(1) is forbidden', $tmpobject->label);
                        return $this->ctl()->forbidden($msg);
                    }
                    unset($tmpobject);

                    // if we're editing a dynamic property, save its property type to cache
                    // for correct processing of the configuration rule (ValidationProperty)
                    $this->var()->setCached('dynamicdata', 'currentproptype', $object->properties['type']);
                }

                $data['preview'] = $preview;

                // Makes this hooks call explictly from DD - why ???
                ////$modinfo = $this->mod()->getInfo($args['moduleid']);
                //$modinfo = $this->mod()->getInfo(182);
                $object->callHooks('modify');
                $data['hooks'] = $object->hookoutput;

                if ($object->objectid == 1) {
                    $data['label'] = $object->properties['label']->value;
                    $this->tpl()->setPageTitle($this->ml('Modify DataObject #(1)', $data['label']));
                } else {
                    $data['label'] = $object->label;
                    $this->tpl()->setPageTitle($this->ml('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
                }

                break;

            case 'clone':
                // user needs admin access to change the access rules
                $data['adminaccess'] = xarSecurity::check('', 0, 'All', $object->objectid . ":" . $name . ":" . "$itemid", '', '', 0, 800);
                $data['name'] = $object->properties['name']->value;
                if ($object->objectid == 1) {
                    $data['label'] = $object->properties['label']->value;
                    $this->tpl()->setPageTitle($this->ml('Clone DataObject #(1)', $data['label']));
                } else {
                    $data['label'] = $object->label;
                    $this->tpl()->setPageTitle($this->ml('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
                }
                break;
        }

        $data['tplmodule'] = $args['tplmodule'];   //TODO: is this needed
        $data['objectid'] = $args['objectid'];
        $data['authid'] = $this->sec()->genAuthKey();
        $data['context'] = $object->getContext();

        if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify.xt') ||
            file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/admin-modify-' . $args['template'] . '.xt')) {
            return $this->tpl()->module($args['tplmodule'], 'admin', 'modify', $data, $args['template']);
        } else {
            return $this->tpl()->module('dynamicdata', 'admin', 'modify', $data, $args['template']);
        }
    }
}
