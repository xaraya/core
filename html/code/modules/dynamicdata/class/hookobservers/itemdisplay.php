<?php

/**
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

use sys;

sys::import('modules.dynamicdata.class.hookobservers.generic');

class ItemDisplay extends DataObjectHookObserver
{
    /** @var string */
    public $type = 'user';

    /**
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return string|void output display string
     */
    public function run(array $extrainfo = [])
    {
        // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
        $modname = $extrainfo['module'];
        $itemtype = $extrainfo['itemtype'];
        $itemid = $extrainfo['itemid'];
        $module_id = $extrainfo['module_id'];

        $descriptorargs = $this->data()->getObjectID([
            'moduleid'  => $module_id,
            'itemtype'  => $itemtype,
        ]);
        // set context if available in hook call
        $object = $this->data()->getObject([
            'name' => $descriptorargs['name'],
            'itemid'   => $itemid,
        ]);

        if (!isset($object) || empty($object->objectid)) {
            return;
        }
        if (!$object->checkAccess('display')) {
            return $this->ml('Display #(1) is forbidden', $object->label);
        }

        $object->getItem();

        if (!empty($object->template)) {
            $template = $object->template;
        } else {
            $template = $object->name;
        }
        return $this->mod()->template(
            'displayhook',
            [
                'properties' => & $object->properties,
                'context' => $object->getContext(),
            ],
            $template
        );
    }
}
