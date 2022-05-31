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
 * @todo move the xml generate code into a template based system.
 */
/**
 * Export an object definition or an object item to XML
 *
 * @author mikespub <mikespub@xaraya.com>
 * @param object $args['objectref'] reference to the object to export, or
 * @param id $args['objectid'] object id of the object to export
 * @param mixed $args['itemid'] item id or 'all' of the item(s) you want to import (optional)
 * @param id $args['module_id'] module id of the object to export (deprecated)
 * @param int $args['itemtype'] item type of the object to export (deprecated)
 */
function dynamicdata_utilapi_export(array $args=[])
{
    if (isset($args['objectref'])) {
        $objectid = $args['objectref']->objectid;
        $itemid = null;
    } else {
        extract($args);

        if (empty($objectid)) {
            $objectid = null;
        }
        // if (empty($module_id)) {
        //     $module_id = xarMod::getRegID('dynamicdata');
        // }
        // if (empty($itemtype)) {
        //     $itemtype = 0;
        // }
        if (empty($itemid)) {
            $itemid = null;
        }
    }
    if (!isset($objectid) && isset($itemid)) {
        $objectid = $itemid;
        $itemid = null;
    }

    if (empty($objectid)) {
        return;
    }

    if (!empty($itemid)) {
        if (is_numeric($itemid)) {
            return xarMod::apiFunc('dynamicdata', 'util', 'export_item', ['objectid' => $objectid, 'itemid' => $itemid]);
        } else {
            return xarMod::apiFunc('dynamicdata', 'util', 'export_items', ['objectid' => $objectid]);
        }
    }

    return xarMod::apiFunc('dynamicdata', 'util', 'export_objectdef', ['objectid' => $objectid]);
}
