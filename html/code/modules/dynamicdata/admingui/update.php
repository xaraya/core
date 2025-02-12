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

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\AdminGui;
use Xaraya\DataObject\UserApi;
use Xaraya\DataObject\AdminApi;
use DataObjectFactory;
use Exception;
use xarController;
use xarMod;
use xarModAlias;
use xarSec;
use xarSecurity;
use xarSession;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin update function
 * @extends MethodClass<AdminGui>
 */
class UpdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update current item
     * This is a standard function that is called with the results of the
     * form supplied by $admingui->modify() to update a current item
     * @param array<string,mixed> $args
     * with
     *     int    objectid
     *     int    module_id
     *     int    itemtype
     *     int    itemid
     *     string return_url
     *     bool   preview
     *     string join
     *     string table
     * @see AdminGui::update()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        /** @var AdminGui $admingui */
        $admingui = $this->admingui();
        $data ??= [];

        if (!$this->var()->check('objectid', $objectid)) {
            return;
        }
        if (!$this->var()->check('itemid', $itemid)) {
            return;
        }
        if (!$this->var()->check('join', $join)) {
            return;
        }
        if (!$this->var()->check('table', $table)) {
            return;
        }
        if (!$this->var()->find('tplmodule', $tplmodule, 'isset', 'dynamicdata')) {
            return;
        }
        if (!$this->var()->check('return_url', $return_url)) {
            return;
        }
        if (!$this->var()->find('preview', $preview, 'isset', 0)) {
            return;
        }

        if (!$this->var()->find('tab', $data['tab'], 'pre:trim:lower:str:1', 'edit')) {
            return;
        }

        // Security
        if (!$this->sec()->checkAccess('EditDynamicData')) {
            return;
        }

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // set context if available in function
        $myobject = $this->data()->getObject(
            ['objectid' => $objectid,
                'join'     => $join,
                'table'    => $table,
                'itemid'   => $itemid]
        );

        $itemid = $myobject->getItem();

        switch ($data['tab']) {

            case 'edit':

                // if we're editing a dynamic property, save its property type to cache
                // for correct processing of the configuration rule (ValidationProperty)
                if ($myobject->objectid == 2) {
                    $this->var()->setCached('dynamicdata', 'currentproptype', $myobject->properties['type']);
                }

                $isvalid = $myobject->checkInput([], 0, 'dd');

                // recover any session var information
                $data = $userapi->sessioncontext(['module' => $tplmodule]);
                extract($data);

                if (!empty($preview) || !$isvalid) {
                    $data = array_merge($data, $adminapi->menu());
                    $data['object'] = & $myobject;

                    $data['objectid'] = $myobject->objectid;
                    $data['itemid'] = $itemid;
                    $data['authid'] = $this->sec()->genAuthKey();
                    $data['preview'] = $preview;
                    //        $data['tplmodule'] = $tplmodule;
                    if (!empty($return_url)) {
                        $data['return_url'] = $return_url;
                    }

                    // Makes this hooks call explictly from DD - why ???
                    ////$modinfo = $this->mod()->getInfo($myobject->moduleid);
                    //$modinfo = $this->mod()->getInfo(182);
                    $myobject->callHooks('modify');
                    $data['hooks'] = $myobject->hookoutput;

                    if ($myobject->objectid == 1) {
                        $data['label'] = $myobject->properties['label']->value;
                        $this->tpl()->setPageTitle($this->ml('Modify DataObject #(1)', $data['label']));
                    } else {
                        $data['label'] = $myobject->label;
                        $this->tpl()->setPageTitle($this->ml('Modify Item #(1) in #(2)', $data['itemid'], $data['label']));
                    }
                    $data['context'] ??= $myobject->getContext();
                    return $this->tpl()->module($tplmodule, 'admin', 'modify', $data);
                }

                // Valid and not previewing, update the object
                $itemid = $myobject->updateItem();
                if (!isset($itemid)) {
                    return;
                } // throw back

                // If we are here then the update is valid: reset the session var
                $this->session()->setVar('ddcontext.' . $tplmodule, ['tplmodule' => $tplmodule]);

                // special case for dynamic objects themselves
                if ($myobject->objectid == 1) {
                    // check if we need to set a module alias (or remove it) for short URLs
                    $name = $myobject->properties['name']->value;
                    $alias = $this->mod()->resolveAlias($name);
                    $isalias = $myobject->properties['isalias']->value;
                    if (!empty($isalias)) {
                        // no alias defined yet, so we create one
                        if ($alias == $name) {
                            $args = ['modName' => 'dynamicdata', 'aliasModName' => $name];
                            $this->mod()->apiFunc('modules', 'admin', 'add_module_alias', $args);
                        }
                    } else {
                        // this was a defined alias, so we remove it
                        if ($alias == 'dynamicdata') {
                            $args = ['modName' => 'dynamicdata', 'aliasModName' => $name];
                            $this->mod()->apiFunc('modules', 'admin', 'delete_module_alias', $args);
                        }
                    }

                }

                break;

            case 'clone':
                // only admins can change access rules
                $adminaccess = xarSecurity::check('', 0, 'All', $myobject->objectid . ":" . $myobject->name . ":" . "All", '', '', 0, 800);

                if (!$adminaccess) {
                    return $this->ctl()->badRequest('no_privileges');
                }

                $name = $myobject->properties['name']->getValue();
                $myobject->properties['name']->setValue();
                if (!$this->var()->find('newname', $newname, 'str', "")) {
                    return;
                }
                if (empty($newname)) {
                    $newname = $name . "_copy";
                }
                $newname = strtolower(str_ireplace(" ", "_", $newname));

                // Check if this object already exists
                try {
                    $testobject = $this->data()->getObject(['name' => $newname]);
                } catch (Exception $e) {
                    return $this->tpl()->module('dynamicdata', 'user', 'errors', ['layout' => 'duplicate_name', 'name' => $newname]);
                }

                $itemtype = $myobject->getNextItemtype(['moduleid' => $myobject->properties['module_id']->getValue()]);
                $myobject->properties['name']->setValue($newname);
                $myobject->properties['label']->setValue(ucfirst($newname));
                $myobject->properties['itemtype']->setValue($itemtype);
                $newitemid = $myobject->createItem(['itemid' => 0]);

                $oldobject = $this->data()->getObject(['objectid' => $itemid]);
                foreach ($oldobject->properties as $property) {
                    $fields['name'] = $property->name;
                    $fields['label'] = $property->label;
                    $fields['objectid'] = $newitemid;
                    $fields['type'] = $property->type;
                    $fields['defaultvalue'] = $property->defaultvalue;
                    $fields['source'] = $property->source;
                    $fields['status'] = $property->status;
                    $fields['seq'] = $property->seq;
                    $fields['configuration'] = $property->configuration;
                    $adminapi->createproperty($fields);
                }

                // Got to the object to modify it
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'modify',
                    ['itemid' => $newitemid]
                ));
                return true;
        }

        if (!empty($return_url)) {
            $this->ctl()->redirect($return_url);
        } elseif ($myobject->objectid == 1) { // for dynamic objects, return to modify
            $this->ctl()->redirect($this->mod()->getURL(
                'admin',
                'modify',
                ['itemid' => $itemid]
            ));
        } elseif ($myobject->objectid == 2) { // for dynamic properties, return to modifyprop
            $objectid = $myobject->properties['objectid']->value;
            $this->ctl()->redirect($this->mod()->getURL(
                'admin',
                'modifyprop',
                ['itemid' => $objectid]
            ));
        } elseif (!empty($table)) {
            $this->ctl()->redirect($this->mod()->getURL(
                'admin',
                'view',
                ['table' => $table]
            ));
        } else {
            $this->ctl()->redirect($this->mod()->getURL(
                'admin',
                'view',
                ['itemid' => $objectid,
                    'tplmodule' => $tplmodule]
            ));
        }
        return true;
    }
}
