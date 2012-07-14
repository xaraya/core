<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * update configuration for a module - hook for ('module','updateconfig','API')
 * Needs $extrainfo['cids'] from arguments, or 'cids' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function categories_adminapi_updateconfighook($args)
{
    sys::import('modules.dynamicdata.class.properties.master');
    $picker = DataPropertyMaster::getProperty(array('name' => 'categorypicker'));
    $picker->checkInput('basecid');

    extract($args);
    return $extrainfo;

/* -------------------- TODO: Remove
    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarMod::getRegId($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','module name', 'admin', 'updateconfighook', 'categories');
        throw new BadParameterException(null, $msg);
    }

    // see what we have to do here (might be empty => we need to delete)
    if (empty($extrainfo['number_of_categories'])) {
        // try to get number of categories from input
        xarVarFetch('number_of_categories', 'int:0:', $numcats, 0, XARVAR_NOT_REQUIRED);
    } else {
        $numcats = $extrainfo['number_of_categories'];
    }
    if (empty($numcats) || !is_numeric($numcats)) {
        $numcats = 0;
    }
    if (!empty($extrainfo['itemtype'])) {
        xarModVars::set($modname,'number_of_categories.'.$extrainfo['itemtype'],$numcats);
    } else {
        xarModVars::set($modname,'number_of_categories',$numcats);
    }

    if (empty($extrainfo['cids']) || !is_array($extrainfo['cids'])) {
        if (!empty($extrainfo['config_cids'])) {
            $cids =& $extrainfo['config_cids'];
        } else {
            // try to get cids from input
            xarVarFetch('config_cids', 'list:int:1:', $cids, NULL, XARVAR_NOT_REQUIRED);
            if (empty($cids) || !is_array($cids)) {
                $cids = array();
            }
        }
    } else {
        $cids = $extrainfo['cids'];
    }
    // get all valid master cids for this module
    // Note : a module might have the same master cid twice (just in case...)
    $mastercids = array();
    foreach ($cids as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        $mastercids[] = $cid;
    }
    if (count($mastercids) > $numcats) {
        $mastercids = array_slice($mastercids,0,$numcats);
    }

    if ($numcats == 0 || count($mastercids) == 0) {
        if (!empty($extrainfo['itemtype'])) {
            xarModVars::set($modname,'mastercids.'.$extrainfo['itemtype'],'');
        } else {
            xarModVars::set($modname,'mastercids','');
        }
    } else {
        if (!empty($extrainfo['itemtype'])) {
            xarModVars::set($modname,'mastercids.'.$extrainfo['itemtype'],
                        join(';',$mastercids));
        } else {
            xarModVars::set($modname,'mastercids',join(';',$mastercids));
        }
    }

    // Return the extra info
    return $extrainfo;
    ---------------------------------------- */
}

?>
