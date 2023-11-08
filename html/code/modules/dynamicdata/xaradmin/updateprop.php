<?php
/**
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
 * Update the dynamic properties for a module + itemtype
 *
 * @return boolean|string|void true on success and redirect to modifyprop
 */
function dynamicdata_admin_updateprop()
{
    /** @var int $objectid */
    if(!xarVar::fetch('objectid', 'isset', $objectid, 1, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $module_id, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'int:1:', $itemtype, 0, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('table', 'isset', $table, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('dd_name', 'isset', $dd_name, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('dd_label', 'isset', $dd_label, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('dd_type', 'isset', $dd_type, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('dd_default', 'isset', $dd_defaultvalue, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('dd_seq', 'isset', $dd_seq, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('dd_translatable', 'isset', $dd_translatable, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('dd_source', 'isset', $dd_source, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('display_dd_status', 'isset', $display_dd_status, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('input_dd_status', 'isset', $input_dd_status, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('dd_configuration', 'isset', $dd_configuration, null, xarVar::DONT_SET)) {
        return;
    }

    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges', 'user', 'errors', ['layout' => 'bad_author']);
    }

    $objectinfo = DataObjectFactory::getObjectInfo(
        [
                                    'objectid' => $objectid,
                                    'moduleid' => $module_id,
                                    'itemtype' => $itemtype,
                                    ]
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

    $fields = xarMod::apiFunc(
        'dynamicdata',
        'user',
        'getprop',
        ['objectid' => $objectid,
                                 'moduleid' => $module_id,
                                 'itemtype' => $itemtype,
                                 'allprops' => true]
    );

    $isprimary = 0;
    $i = 0;
    // update old fields
    foreach ($fields as $name => $field) {
        $id = $field['id'];
        $i++;
        if (empty($dd_label[$id])) {
            $property = DataPropertyMaster::getProperty(['type' => $field['type']]);
            $res = $property->removeFromObject(['object_id' => $objectid]);
            // delete property (and corresponding data) in xaradminapi.php
            if (!xarMod::apiFunc(
                'dynamicdata',
                'admin',
                'deleteprop',
                ['id' => $id]
            )) {
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
            if (!xarMod::apiFunc(
                'dynamicdata',
                'admin',
                'updateprop',
                ['id'            => $id,
                                    'name'          => $dd_name[$id],
                                    'label'         => $dd_label[$id],
                                    'type'          => $dd_type[$id],
                                    'defaultvalue'  => $dd_defaultvalue[$id],
                                    'seq'           => $dd_seq[$id],
                                    'translatable'  => $dd_translatable[$id],
                                    'source'        => $dd_source[$id],
                                    'status'        => $dd_status[$id],
                                    'configuration' => $dd_configuration[$id]]
            )) {
                return;
            }
            if (DataPropertyMaster::isPrimaryType($dd_type[$id])) { // item id
                $isprimary = 1;
            }

            // If we changed the property type, run the appropriate methods
            if ($field['type'] != $dd_type[$id]) {
                $property = DataPropertyMaster::getProperty(['type' => $field['type']]);
                $res = $property->removeFromObject(['object_id' => $objectid]);
                $property = DataPropertyMaster::getProperty(['type' => $dd_type[$id]]);
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
        $id = xarMod::apiFunc(
            'dynamicdata',
            'admin',
            'createproperty',
            ['name' => $name,
                                      'label' => $dd_label[0],
                                      'objectid' => $objectid,
                                     // 'moduleid' => $module_id,
                                     // 'itemtype' => $itemtype,
                                      'type' => $dd_type[0],
                                      'defaultvalue' => $dd_defaultvalue[0],
                                      'source' => $dd_source[0],
                                      'status' => $dd_status[0],
                                      'seq' => $i]
        );
        if (empty($id)) {
            return;
        }

        if (DataPropertyMaster::isPrimaryType($dd_type[0])) { // item id
            $isprimary = 1;
        }
        $property = DataPropertyMaster::getProperty(['type' => $dd_type[0]]);
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

    xarController::redirect(xarController::URL(
        'dynamicdata',
        'admin',
        'modifyprop',
        ['itemid'    => $objectid,
                              'table'    => $table]
    ));
    return true;
}
