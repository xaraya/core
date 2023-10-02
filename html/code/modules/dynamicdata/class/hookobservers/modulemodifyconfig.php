<?php
/**
 * Modify configuration for a module
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

namespace Xaraya\DataObject\HookObservers;

use xarController;
use xarMod;
use xarSecurity;
use xarTpl;
use DataObjectDescriptor;
use DataObjectMaster;
use DataPropertyMaster;
use BadParameterException;
use EmptyParameterException;
use sys;

class ModuleModifyconfig extends DataObjectHookObserver
{
    /**
     * modify configuration for a module - hook for ('module','modifyconfig','GUI')
     *
     * @param array<string, mixed> $args
     * with
     *     int $args['objectid'] ID of the object
     *     array $args['extrainfo'] extra information
     * @return string|void output display string
     * @throws EmptyParameterException
     * @throws BadParameterException
     */
    public static function run(array $args = [])
    {
        // Security
        if(!xarSecurity::check('AdminDynamicData')) {
            return;
        }

        extract($args);

        if (!isset($extrainfo)) {
            throw new EmptyParameterException('extrainfo');
        }

        // When called via hooks, the module name may be empty, so we get it from
        // the current module
        if (empty($extrainfo['module'])) {
            $modname = xarMod::getName();
        } else {
            $modname = $extrainfo['module'];
        }

        // don't allow hooking to yourself in DD
        if ($modname == 'dynamicdata') {
            return '';
        }

        $module_id = xarMod::getRegID($modname);
        if (empty($module_id)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['module name', 'admin', 'modifyconfighook', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        if (!empty($extrainfo['itemtype'])) {
            $itemtype = $extrainfo['itemtype'];
        } else {
            $itemtype = null;
        }

        if (!xarMod::apiLoad('dynamicdata', 'user')) {
            return;
        }

        sys::import('modules.dynamicdata.class.objects.master');
        $args = DataObjectDescriptor::getObjectID(['module'  => $module_id,
                                           'itemtype'  => $itemtype]);

        $fields = xarMod::apiFunc(
            'dynamicdata',
            'user',
            'getprop',
            ['objectid' => $args['objectid']]
        );
        if (!isset($fields) || $fields == false) {
            $fields = [];
        }

        $labels = [
            'id' => xarML('ID'),
            'name' => xarML('Name'),
            'label' => xarML('Label'),
            'type' => xarML('Field Format'),
            'defaultvalue' => xarML('Default'),
            'source' => xarML('Data Source'),
            'configuration' => xarML('Configuration'),
        ];

        $labels['dynamicdata'] = xarML('Dynamic Data Fields');
        $labels['config'] = xarML('modify');

        $data = [];
        $data['labels'] = $labels;
        $data['link'] = xarController::URL(
            'dynamicdata',
            'admin',
            'modifyprop',
            ['module_id' => $module_id,
            'itemtype' => $itemtype]
        );
        $data['fields'] = $fields;
        $data['fieldtypeprop'] = & DataPropertyMaster::getProperty(['type' => 'fieldtype']);

        $object = DataObjectMaster::getObject(['name' => $args['name']]);

        if (!empty($object)) {
            if (!empty($object->template)) {
                $template = $object->template;
            } else {
                $template = $object->name;
            }
        } else {
            $template = null;
        }
        return xarTpl::module('dynamicdata', 'admin', 'modifyconfighook', $data, $template);
    }
}
