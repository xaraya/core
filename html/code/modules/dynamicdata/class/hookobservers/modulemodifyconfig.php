<?php

/**
 * Modify configuration for a module
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
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
use DataObjectFactory;
use DataPropertyMaster;
use BadParameterException;
use EmptyParameterException;
use sys;

sys::import('modules.dynamicdata.class.hookobservers.generic');

class ModuleModifyconfig extends DataObjectHookObserver
{
    /**
     * modify configuration for a module - hook for ('module','modifyconfig','GUI')
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return string|void output display string
     * @throws EmptyParameterException
     * @throws BadParameterException
     */
    public function run(array $extrainfo = [])
    {
        // Security
        if (!xarSecurity::check('AdminDynamicData')) {
            return;
        }

        // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
        $modname = $extrainfo['module'];
        $itemtype = $extrainfo['itemtype'];
        $module_id = $extrainfo['module_id'];

        // don't allow hooking to yourself in DD
        if ($modname == 'dynamicdata') {
            return '';
        }

        if (!xarMod::apiLoad('dynamicdata', 'user')) {
            return;
        }

        $args = DataObjectDescriptor::getObjectID([
            'moduleid'  => $module_id,
            'itemtype'  => $itemtype,
        ]);

        // @todo move to object method here too
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

        // set context if available in hook call
        $object = DataObjectFactory::getObject([
            'name' => $args['name'],
        ], $this->getContext());

        if (!empty($object)) {
            if (!empty($object->template)) {
                $template = $object->template;
            } else {
                $template = $object->name;
            }
            $data['context'] = $object->getContext();
        } else {
            $template = null;
            $data['context'] = $this->getContext();
        }
        return xarTpl::module(
            'dynamicdata',
            'admin',
            'modifyconfighook',
            $data,
            $template
        );
    }
}
