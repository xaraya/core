<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * update linkage for an item - hook for ('item','update','API')
 * Needs $extrainfo['cids'] from arguments, or 'cids' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function categories_adminapi_updatehook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'createhook', 'categories');
        throw new BadParameterException(null, $msg);
    }

/* ---------------------------- TODO: Remove
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

    // see what we have to do here (might be empty => we need to unlink)
    if (empty($extrainfo['cids'])) {
        if (!empty($extrainfo['modify_cids'])) {
            $extrainfo['cids'] =& $extrainfo['modify_cids'];
        } else {
            // try to get cids from input
            xarVarFetch('modify_cids', 'list:int:1:', $cids, NULL, XARVAR_NOT_REQUIRED);
            if (empty($cids) || !is_array($cids)) {
                $extrainfo['cids'] = array();
            } else {
                $extrainfo['cids'] =& $cids;
            }
        }
    }
    // get all valid cids for this item
    // Note : an item may *not* belong to the same cid twice
    $seencid = array();
    foreach ($extrainfo['cids'] as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        $seencid[$cid] = 1;
    }
    $cids = array_keys($seencid);

    if (count($cids) == 0) {
        if (!xarMod::apiFunc('categories', 'admin', 'unlink',
                          array('iid' => $objectid,
                                'itemtype' => $itemtype,
                                'modid' => $modid))) {
            return;
        }
    } elseif (!xarMod::apiFunc('categories', 'admin', 'linkcat',
                            array('cids'  => $cids,
                                  'iids'  => array($objectid),
                                  'itemtype' => $itemtype,
                                  'modid' => $modid,
                                  'clean_first' => true))) {
        return;
    }

------------------------------- */

    sys::import('modules.dynamicdata.class.properties.master');
    $categories = DataPropertyMaster::getProperty(array('name' => 'categories'));
    if ($categories->checkInput('hookedcategories')) {
// CHECKME: aren't we supposed to save the categories here ?
        $categories->updateValue($objectid);
    }

    // Return the extra info
    return $extrainfo;
}

?>
