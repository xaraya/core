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
use BadParameterException;
use DataObjectFactory;
use DataPropertyMaster;
use xarController;
use xarMod;
use xarModHooks;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin updateprop function
 * @extends MethodClass<AdminGui>
 */
class UpdatepropMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update the dynamic properties for a module + itemtype
     * @return bool|string|void true on success and redirect to modifyprop
     * @see AdminGui::updateprop()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        if (!$this->var()->check('objectid', $objectid, 'isset', 1)) {
            return;
        }
        /** @var int $objectid */
        if (!$this->var()->check('module_id', $module_id)) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype, 'int:1:', 0)) {
            return;
        }
        if (!$this->var()->check('table', $table)) {
            return;
        }
        if (!$this->var()->check('dd_name', $dd_name)) {
            return;
        }
        if (!$this->var()->check('dd_label', $dd_label)) {
            return;
        }
        if (!$this->var()->check('dd_type', $dd_type)) {
            return;
        }
        if (!$this->var()->check('dd_default', $dd_defaultvalue)) {
            return;
        }
        if (!$this->var()->check('dd_seq', $dd_seq)) {
            return;
        }
        if (!$this->var()->check('dd_translatable', $dd_translatable)) {
            return;
        }
        if (!$this->var()->check('dd_source', $dd_source)) {
            return;
        }
        if (!$this->var()->check('display_dd_status', $display_dd_status)) {
            return;
        }
        if (!$this->var()->check('input_dd_status', $input_dd_status)) {
            return;
        }
        if (!$this->var()->check('dd_configuration', $dd_configuration)) {
            return;
        }

        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        $objectinfo = $this->data()->getObjectInfo(
            ['objectid' => $objectid,
                'moduleid' => $module_id,
                'itemtype' => $itemtype]
        );
        if (isset($objectinfo)) {
            $objectid = $objectinfo['objectid'];
            $module_id = $objectinfo['moduleid'];
            $itemtype = $objectinfo['itemtype'];
        } elseif (!empty($module_id)) {
            $modinfo = xarMod::getInfo($module_id);
            if (!empty($modinfo['name'])) {
                $name = $modinfo['name'];
                if (!empty($itemtype)) {
                    $name .= '_' . $itemtype;
                }
                $objectid = DataObjectFactory::createObject(
                    ['moduleid' => $module_id,
                        'itemtype' => $itemtype,
                        'name' => $name,
                        'label' => ucfirst($name)]
                );
                if (!isset($objectid)) {
                    return;
                }
            }
        }

        if (empty($module_id)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['module id', 'admin', 'updateprop', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        $fields = $userapi->getprop(['objectid' => $objectid,
                'moduleid' => $module_id,
                'itemtype' => $itemtype,
                'allprops' => true]);

        $isprimary = 0;
        $i = 0;
        // update old fields
        foreach ($fields as $name => $field) {
            $id = $field['id'];
            $i++;
            if (empty($dd_label[$id])) {
                $property = $this->prop()->getProperty(['type' => $field['type']]);
                $res = $property->removeFromObject(['object_id' => $objectid]);
                // delete property (and corresponding data) in xaradminapi.php
                if (!$adminapi->deleteprop(['id' => $id])) {
                    return;
                }
            } else {
                // TODO : only if necessary
                // update property in xaradminapi.php
                if (!isset($dd_defaultvalue[$id])) {
                    $dd_defaultvalue[$id] = null;
                } elseif (!empty($dd_defaultvalue[$id]) && preg_match('/\[LF\]/', $dd_defaultvalue[$id])) {
                    // replace [LF] with line-feed again
                    $lf = chr(10);
                    $dd_defaultvalue[$id] = preg_replace('/\[LF\]/', $lf, $dd_defaultvalue[$id]);
                }
                if (!isset($dd_configuration[$id])) {
                    $dd_configuration[$id] = null;
                }
                if (!isset($display_dd_status[$id])) {
                    $display_dd_status[$id] = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
                }
                if (!isset($input_dd_status[$id])) {
                    $input_dd_status[$id] = DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
                }
                $dd_status[$id] = $display_dd_status[$id] + $input_dd_status[$id];
                if (!isset($dd_translatable[$id])) {
                    $dd_translatable[$id] = 0;
                }
                if (!$adminapi->updateprop(['id'            => $id,
                        'name'          => $dd_name[$id],
                        'label'         => $dd_label[$id],
                        'type'          => $dd_type[$id],
                        'defaultvalue'  => $dd_defaultvalue[$id],
                        'seq'           => $dd_seq[$id],
                        'translatable'  => $dd_translatable[$id],
                        'source'        => $dd_source[$id],
                        'status'        => $dd_status[$id],
                        'configuration' => $dd_configuration[$id]])) {
                    return;
                }
                if (DataPropertyMaster::isPrimaryType($dd_type[$id])) { // item id
                    $isprimary = 1;
                }

                // If we changed the property type, run the appropriate methods
                if ($field['type'] != $dd_type[$id]) {
                    $property = $this->prop()->getProperty(['type' => $field['type']]);
                    $res = $property->removeFromObject(['object_id' => $objectid]);
                    $property = $this->prop()->getProperty(['type' => $dd_type[$id]]);
                    $res = $property->addToObject(['object_id' => $objectid]);
                }
            }
        }
        $i++;
        // insert new field
        if (!empty($dd_label[0]) && !empty($dd_type[0])) {
            // create new property in xaradminapi.php
            $name = strtolower($dd_label[0]);
            $name = preg_replace('/[^a-z0-9_]+/', '_', $name);
            $name = preg_replace('/_$/', '', $name);
            if (!isset($display_dd_status[0])) {
                $display_dd_status[0] = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
            }
            if (!isset($input_dd_status[0])) {
                $input_dd_status[0] = DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
            }
            $dd_status[0] = $display_dd_status[0] + $input_dd_status[0];
            $id = $adminapi->createproperty(['name' => $name,
                    'label' => $dd_label[0],
                    'objectid' => $objectid,
                    // 'moduleid' => $module_id,
                    // 'itemtype' => $itemtype,
                    'type' => $dd_type[0],
                    'defaultvalue' => $dd_defaultvalue[0],
                    'source' => $dd_source[0],
                    'status' => $dd_status[0],
                    'seq' => $i]);
            if (empty($id)) {
                return;
            }

            if (DataPropertyMaster::isPrimaryType($dd_type[0])) { // item id
                $isprimary = 1;
            }
            $property = $this->prop()->getProperty(['type' => $dd_type[0]]);
            $res = $property->addToObject(['object_id' => $objectid]);
        }

        // CHECKME: flush the variable cache if necessary
        DataObjectFactory::flushVariableCache(['objectid' => $objectid]);

        if ($isprimary) {
            $modinfo = xarMod::getInfo($module_id);
            xarModHooks::call(
                'module',
                'updateconfig',
                $modinfo['name'],
                ['module' => $modinfo['name'],
                    'itemtype' => $itemtype]
            );
        }

        $this->ctl()->redirect($this->mod()->getURL(
            'admin',
            'modifyprop',
            ['itemid'    => $objectid,
                'table'    => $table]
        ));
        return true;
    }
}
