<?php

/**
 * Select dynamicdata for a new item
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

class ItemNew extends DataObjectHookObserver
{
    /**
     * select dynamicdata for a new item - hook for ('item','new','GUI')
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return string|void output display string
     */
    public function run(array $extrainfo = [])
    {
        $xar = $this->getServicesClass();
        // Security
        if (!$xar->sec()->checkAccess('AddDynamicData')) {
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

        $descriptorargs = $xar->data()->getObjectID([
            'moduleid'  => $module_id,
            'itemtype'  => $itemtype,
        ]);
        $object = $xar->data()->getObject([
            'name' => $descriptorargs['name'],
        ]);

        if (!isset($object) || empty($object->objectid)) {
            return;
        }

        // if we are in preview mode, we need to check for any preview values
        $xar->var()->check('preview', $preview);
        if (!empty($preview)) {
            $object->checkInput();
        }

        if (!empty($object->template)) {
            $template = $object->template;
        } else {
            $template = $object->name;
        }

        $properties = $object->getProperties();
        return $this->render(
            'newhook',
            [
                'properties' => $properties,
                'context' => $object->getContext(),
            ],
            $template
        );
    }
}
