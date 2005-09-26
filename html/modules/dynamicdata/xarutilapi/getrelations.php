<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * (try to) get the relationships between a particular module and others (e.g. hooks)
// TODO: allow other kinds of relationships than hooks
// TODO: allow modules to specify their own relationships
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_utilapi_getrelations($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($modid)) {
        $modid = xarModGetIDFromName(xarModGetName());
    }
    $modinfo = xarModGetInfo($modid);
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id ' . xarVarPrepForDisplay($modid);
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'util', 'getrelations', 'DynamicData');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($propertybag["$modid:$itemtype"])) {
        return $propertybag["$modid:$itemtype"];
    }

    // get the list of static properties for this module
    $static = xarModAPIFunc('dynamicdata','util','getstatic',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype));

    // get the list of hook modules that are enabled for this module
// TODO: get all hooks types, not only item display hooks
//    $hooklist = xarModGetHookList($modinfo['name'],'item','display');
    $hooklist = array_merge(xarModGetHookList($modinfo['name'],'item','display'),
                            xarModGetHookList($modinfo['name'],'item','update'));
    $modlist = array();
    foreach ($hooklist as $hook) {
        $modlist[$hook['module']] = 1;
    }

    $relations = array();
    if (count($modlist) > 0) {
        // first look for the (possible) item id field in the current module
        $itemid = '???';
        foreach ($static as $field) {
            if ($field['type'] == 21) { // Item ID
                $itemid = $field['source'];
                break;
            }
        }
        // for each enabled hook module
        foreach ($modlist as $mod => $val) {
            // get the list of static properties for this hook module
            $modstatic = xarModAPIFunc('dynamicdata','util','getstatic',
                                       array('modid' => xarModGetIDFromName($mod)));
                                       // skip this for now
                                       //      'itemtype' => $itemtype));
        // TODO: automatically find the link(s) on module, item type, item id etc.
        //       or should hook modules tell us that ?
            $links = array();
            foreach ($modstatic as $field) {

        /* for hook modules, those should define the fields *relating to* other modules (not their own item ids etc.)
                // try predefined field types first
                if ($field['type'] == 19) { // Module
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');
                } elseif ($field['type'] == 20) { // Item Type
                    $links[] = array('from' => $field['source'], 'to' => $itemtype, 'type' => 'itemtype');
                } elseif ($field['type'] == 21) { // Item ID
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
                }
        */
                // try to guess based on field names *cough*
                // link on module name/id
                if (preg_match('/_module$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modinfo['name'], 'type' => 'modulename');
                } elseif (preg_match('/_moduleid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');
                } elseif (preg_match('/_modid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');

                // link on item type
                } elseif (preg_match('/_itemtype$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemtype, 'type' => 'itemtype');

                // link on item id
                } elseif (preg_match('/_itemid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
                } elseif (preg_match('/_iid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
                }
            }
            $relations[] = array('module' => $mod,
                                 'fields' => $modstatic,
                                 'links'  => $links);
        }
    }
    return $relations;
}


?>