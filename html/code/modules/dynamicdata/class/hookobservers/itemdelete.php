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

use BadParameterException;

class ItemDelete extends DataObjectHookObserver
{
    /**
     * delete fields for an item - hook for ('item','delete','API')
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return array<mixed> true on success, false on failure
     * @throws BadParameterException
     */
    public function run(array $extrainfo = [])
    {
        // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
        $modname = $extrainfo['module'];
        $itemtype = $extrainfo['itemtype'];
        $itemid = $extrainfo['itemid'];
        $module_id = $extrainfo['module_id'];

        // don't allow hooking to yourself in DD
        if ($modname == 'dynamicdata') {
            return $extrainfo;
        }

        if (empty($itemid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['item id', 'admin', 'deletehook', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        $descriptorargs = $this->data()->getObjectID([
            'moduleid'  => $module_id,
            'itemtype'  => $itemtype,
        ]);
        // set context if available in hook call
        $object = $this->data()->getObject([
            'name' => $descriptorargs['name'],
            'itemid'   => $itemid,
        ]);

        // If no object returned, bail and pass the extrainfo to the next hook
        if (!isset($object) || empty($object->objectid)) {
            return $extrainfo;
        }

        if (!$object->checkAccess('delete')) {
            return $extrainfo;
        }

        $object->getItem();
        $itemid = $object->deleteItem();

        return $extrainfo;
    }
}
