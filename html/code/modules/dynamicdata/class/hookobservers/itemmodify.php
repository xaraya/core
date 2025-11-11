<?php

/**
 * Modify Dynamic data for an Item
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

class ItemModify extends DataObjectHookObserver
{
    /**
     * modify dynamicdata for an item - hook for ('item','modify','GUI')
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return string|void output display string
     */
    public function run(array $extrainfo = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditDynamicData')) {
            return;
        }

        // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
        $modname = $extrainfo['module'];
        $itemtype = $extrainfo['itemtype'];
        $itemid = $extrainfo['itemid'];
        $module_id = $extrainfo['module_id'];

        // don't allow hooking to yourself in DD
        if ($modname == 'dynamicdata') {
            return '';
        }

        $descriptorargs = $this->data()->getObjectID([
            'moduleid'  => $module_id,
            'itemtype'  => $itemtype,
        ]);
        // set context if available in hook call
        $object = $this->data()->getObject([
            'name' => $descriptorargs['name'],
        ]);

        if (!isset($object) || empty($object->objectid)) {
            return;
        }

        $object->getItem(['itemid' => $itemid]);

        // if we are in preview mode, we need to check for any preview values
        $this->var()->check('preview', $preview);
        if (!empty($preview)) {
            $object->checkInput();
        }

        if (!empty($object->template)) {
            $template = $object->template;
        } else {
            $template = $object->name;
        }

        $properties = $object->getProperties();
        return $this->mod()->template(
            'modifyhook',
            [
                'properties' => $properties,
                'context' => $object->getContext(),
            ],
            $template
        );
    }
}
