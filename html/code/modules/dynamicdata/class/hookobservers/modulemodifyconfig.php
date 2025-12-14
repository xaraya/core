<?php

/**
 * Modify configuration for a module
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\HookObservers;

use BadParameterException;
use EmptyParameterException;

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
        $xar = $this->getServicesClass();
        // Security
        if (!$xar->sec()->checkAccess('AdminDynamicData')) {
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

        $args = $xar->data()->getObjectID([
            'moduleid'  => $module_id,
            'itemtype'  => $itemtype,
        ]);

        // @todo move to object method here too
        $fields = $xar->mod()->apiMethod(
            'dynamicdata',
            'userapi',
            'getprop',
            ['objectid' => $args['objectid']]
        );
        if (!isset($fields) || $fields == false) {
            $fields = [];
        }

        $labels = [
            'id' => $xar->ml('ID'),
            'name' => $xar->ml('Name'),
            'label' => $xar->ml('Label'),
            'type' => $xar->ml('Field Format'),
            'defaultvalue' => $xar->ml('Default'),
            'source' => $xar->ml('Data Source'),
            'configuration' => $xar->ml('Configuration'),
        ];

        $labels['dynamicdata'] = $xar->ml('Dynamic Data Fields');
        $labels['config'] = $xar->ml('modify');

        $data = [];
        $data['labels'] = $labels;
        $data['link'] = $xar->ctl()->getModuleURL(
            'dynamicdata',
            'admin',
            'modifyprop',
            ['module_id' => $module_id,
                'itemtype' => $itemtype]
        );
        $data['fields'] = $fields;
        $data['fieldtypeprop'] = $xar->prop()->getProperty(['type' => 'fieldtype']);

        // set context if available in hook call
        $object = $xar->data()->getObject([
            'name' => $args['name'],
        ]);

        if (!empty($object)) {
            if (!empty($object->template)) {
                $template = $object->template;
            } else {
                $template = $object->name;
            }
            $data['context'] = $object->getContext();
        } else {
            $template = null;
            $data['context'] = $xar->getContext();
        }
        return $this->render(
            'modifyconfighook',
            $data,
            $template
        );
    }
}
