<?php
/**
 * @package core\hooks
 * @subpackage hooks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

sys::import('xaraya.structures.events.observer');

class HookObserver extends EventObserver implements ixarEventObserver
{
    public $module = "modules";
    
    function validate($extrainfo = array())
    {
        // Check whether a valid array was passed
        if (!isset($extrainfo) || !is_array($extrainfo)) {
            $msg = xarML('Invalid #(1) in function #(2)() in module #(3)',
                         'extrainfo', 'updatehook', 'pubsub');
            throw new Exception($msg);
        }

        // We can use hooks via module/itemtype or object
        if (!isset($extrainfo['module']) && !isset($extrainfo['object'])) {
            $msg = xarML('Missing #(1) in function #(2)() in module #(3)',
                         'module or object', 'updatehook', 'pubsub');
            throw new Exception($msg);
        }
        
        // When called via hooks, the module name may be empty, so we get it from
        // the current module
        if (isset($extrainfo['module']) && is_string($extrainfo['module'])) {
            $modname = $extrainfo['module'];
        } else {
            $modname = xarMod::getName();
        }
        $module_id = xarMod::getRegID($modname);
        if (!$module_id) {
            return false; // throw back
        } else {
            $extrainfo['module_id'] = $module_id;
        }

        // If we have an object, we need to get its ID
        if (isset($extrainfo['object']) && is_string($extrainfo['object'])) {
            sys::import('modules.dynamicdata.class.properties.master');
            $object = DataObjectMaster::getObjectList(array('name' => 'objects'));
            $q = $object->dataquery;
            $q->eq('name', $extrainfo['object']);
            $items = $object->getItems();
            $item = reset($items);
            $extrainfo['object_id'] = (int)$item['objectid'];
        }
        
        // Assign the itemtype if we don't have one
        if (!isset($extrainfo['itemtype']) || !is_numeric($extrainfo['itemtype'])) {
            $extrainfo['itemtype'] = 0;
        }

        // Assign the url if we don't have one
        if (!isset($extrainfo['url'])) {
            $extrainfo['url'] = '';
        }

        // Check for a category ID
        if (isset($extrainfo['cid']) && is_numeric($extrainfo['cid'])) {
            $cid = $extrainfo['cid'];
        } elseif (isset($extrainfo['cids'][0]) && is_numeric($extrainfo['cids'][0])) {
        // TODO: loop over all categories
            $cid = $extrainfo['cids'][0];
        } else {
            $cid = 1;
        }
        $extrainfo['cid'] = $cid;

        return $extrainfo;
    }
}
?>