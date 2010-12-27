<?php

/**
 * create linkage for an item - hook for ('item','create','API')
 * Needs $extrainfo['cids'] from arguments, or 'cids' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function categories_adminapi_createhook($args)
{
    extract($args);

    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $extrainfo = array();
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'createhook', 'categories');
        throw new BadParameterException(null, $msg);
    }

/* ---------------------------- TODO: Remove
    // see if we have anything to do here (might be empty => return)
    if (empty($extrainfo['cids']) || count($extrainfo['cids']) == 0) {
        if (!empty($extrainfo['new_cids'])) {
            $extrainfo['cids'] =& $extrainfo['new_cids'];
        } else {
            // try to get cids from input
            xarVarFetch('new_cids', 'list:int:1:', $cids, NULL, XARVAR_NOT_REQUIRED);
            if (empty($cids) || !is_array($cids)) {
                $extrainfo['cids'] = array();
                // no categories to link here
                return $extrainfo;
            } else {
                $extrainfo['cids'] = $cids;
            }
        }
    }
    // get all valid cids
    $seencid = array();
    foreach ($extrainfo['cids'] as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        $seencid[$cid] = 1;
    }
    if (count($seencid) == 0) {
        // no categories to link here
        return $extrainfo;
    }
    $cids = array_keys($seencid);

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarMod::getRegId($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','module name', 'admin', 'createhook', 'categories');
        throw new BadParameterException(null, $msg);
    }
    if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    if (!xarMod::apiFunc('categories', 'admin', 'linkcat',
                      array('cids'  => $cids,
                            'iids'  => array($objectid),
                            'itemtype' => $itemtype,
                            'modid' => $modid))) {
        return;
    }
------------------------------- */

    sys::import('modules.dynamicdata.class.properties.master');
    $categories = DataPropertyMaster::getProperty(array('name' => 'categories'));
    if ($categories->checkInput('hookedcategories')) {
// CHECKME: aren't we supposed to save the categories here ?
        $categories->createValue($objectid);
    }

    // Return the extra info
    return $extrainfo;
}

?>
