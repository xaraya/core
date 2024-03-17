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
 * (try to) get the relationships between a particular module and others (e.g. hooks)
// TODO: allow other kinds of relationships than hooks
// TODO: allow modules to specify their own relationships
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args
 * with
 *     $args['module'] module name of the item fields, or
 *     $args['module_id'] module id of the item field to get
 *     $args['itemtype'] item type of the item field to get
 * @return mixed value of the field, or false on failure
 */
function dynamicdata_utilapi_getrelations(array $args = [], $context = null)
{
    static $propertybag = [];

    extract($args);

    if (empty($module_id) && !empty($module)) {
        $module_id = xarMod::getRegID($module);
    }
    if (empty($module_id)) {
        $module_id = xarMod::getRegID(xarMod::getName());
    }
    $modinfo = xarMod::getInfo($module_id);
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = [];
    if (!isset($module_id) || !is_numeric($module_id) || empty($modinfo['name'])) {
        $invalid[] = 'module id ' . xarVar::prepForDisplay($module_id);
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = [join(', ', $invalid), 'util', 'getrelations', 'DynamicData'];
        throw new BadParameterException($vars, $msg);
    }

    if (isset($propertybag["$module_id:$itemtype"])) {
        return $propertybag["$module_id:$itemtype"];
    }

    // get the list of static properties for this module
    $static = xarMod::apiFunc(
        'dynamicdata',
        'util',
        'getstatic',
        ['module_id' => $module_id,
        'itemtype' => $itemtype],
        $context
    );

    // get the list of hook modules that are enabled for this module
    // TODO: get all hooks types, not only item display hooks
    //    $hooklist = xarModHooks::getList($modinfo['name'],'item','display');
    $hooklist = array_merge(
        xarModHooks::getList($modinfo['name'], 'item', 'display'),
        xarModHooks::getList($modinfo['name'], 'item', 'update'),
        xarModHooks::getList($modinfo['name'], 'module', 'remove')
    );
    $modlist = [];
    foreach ($hooklist as $hook) {
        $modlist[$hook['module']] = 1;
    }

    $relations = [];
    if (count($modlist) > 0) {
        // first look for the (possible) item id field in the current module
        $itemid = '???';
        foreach ($static as $field) {
            if (DataPropertyMaster::isPrimaryType($field['type'])) { // Item ID
                $itemid = $field['source'];
                break;
            }
        }
        // for each enabled hook module
        foreach ($modlist as $mod => $val) {
            // get the list of static properties for this hook module
            $modstatic = xarMod::apiFunc(
                'dynamicdata',
                'util',
                'getstatic',
                ['module_id' => xarMod::getRegID($mod)],
                $context
            );
            // skip this for now
            //      'itemtype' => $itemtype));
            // TODO: automatically find the link(s) on module, item type, item id etc.
            //       or should hook modules tell us that ?
            $links = [];
            foreach ($modstatic as $field) {

                // try to guess based on field names *cough*
                // link on module name/id
                if (preg_match('/_module$/', $field['source'])) {
                    $links[] = ['from' => $field['source'], 'to' => $modinfo['name'], 'type' => 'modulename'];
                } elseif (preg_match('/_moduleid$/', $field['source'])) {
                    $links[] = ['from' => $field['source'], 'to' => $module_id, 'type' => 'moduleid'];
                } elseif (preg_match('/_modid$/', $field['source'])) {
                    $links[] = ['from' => $field['source'], 'to' => $module_id, 'type' => 'moduleid'];
                } elseif ('module_id' == $field['name']) {
                    $links[] = ['from' => $field['source'], 'to' => $module_id, 'type' => 'moduleid'];

                    // link on item type
                } elseif (preg_match('/_itemtype$/', $field['source'])) {
                    $links[] = ['from' => $field['source'], 'to' => $itemtype, 'type' => 'itemtype'];
                } elseif ('itemtype' == $field['name']) {
                    $links[] = ['from' => $field['source'], 'to' => $itemtype, 'type' => 'itemtype'];

                    // link on item id
                } elseif (preg_match('/_itemid$/', $field['source'])) {
                    $links[] = ['from' => $field['source'], 'to' => $itemid, 'type' => 'itemid'];
                } elseif (preg_match('/_iid$/', $field['source'])) {
                    $links[] = ['from' => $field['source'], 'to' => $itemid, 'type' => 'itemid'];
                } elseif ('itemid' == $field['name']) {
                    $links[] = ['from' => $field['source'], 'to' => $itemid, 'type' => 'itemid'];
                }
            }
            $relations[] = [
                'module' => $mod,
                'fields' => $modstatic,
                'links'  => $links,
            ];
        }
    }
    return $relations;
}
