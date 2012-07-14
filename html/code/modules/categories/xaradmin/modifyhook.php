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
 * modify categories for an item - hook for ('item','modify','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function categories_admin_modifyhook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'modifyhook', 'categories');
        throw new BadParameterException(null, $msg);
    }
    $data['itemid'] = $objectid;

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $data['module'] = $modname;
    $modid = xarMod::getRegId($modname);

/* ---------------------------- TODO: Remove
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','module name', 'admin', 'modifyhook', 'categories');
        throw new BadParameterException(null, $msg);
    }
    if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    if (empty($extrainfo['number_of_categories'])) {
        // get number of categories from current settings
        if (!empty($extrainfo['itemtype'])) {
            $numcats = (int) xarModVars::get($modname, 'number_of_categories.'.$extrainfo['itemtype']);
        } else {
            $numcats = (int) xarModVars::get($modname, 'number_of_categories');
        }
    } else {
        $numcats = (int) $extrainfo['number_of_categories'];
    }
    if (empty($numcats) || !is_numeric($numcats)) {
        $numcats = 0;
        // no categories to show here -> return empty output
        return '';
    }

------------------------------- */
    // Security check (return empty hook output if not allowed) - to be refined per cat
    if (!empty($extrainfo['itemtype'])) {
        $modtype = $extrainfo['itemtype'];
        $data['itemtype'] = $extrainfo['itemtype'];
    } else {
        $modtype = 'All';
        $data['itemtype'] = 0;
    }
    if (!xarSecurityCheck('EditCategoryLink',0,'Link',"$modid:$modtype:All:All")) return '';

/* ---------------------------- TODO: Remove
    if (empty($extrainfo['mastercids']) || !is_array($extrainfo['mastercids'])) {
        // try to get cids from current settings
        if (!empty($extrainfo['itemtype'])) {
            $cidlist = xarModVars::get($modname,'mastercids.'.$extrainfo['itemtype']);
        } else {
            $cidlist = xarModVars::get($modname,'mastercids');
        }
        if (empty($cidlist)) {
            $mastercids = array();
        } else {
            $mastercids = explode(';',$cidlist);
        }
    } else {
        $mastercids = $extrainfo['mastercids'];
    }

    // used e.g. for previews of modified items
    if (empty($extrainfo['cids']) || !is_array($extrainfo['cids'])) {
        if (!empty($extrainfo['modify_cids'])) {
            $cids = $extrainfo['modify_cids'];
        } else {
            // try to get cids from input
            xarVarFetch('modify_cids', 'list:int:1:', $cids, NULL, XARVAR_NOT_REQUIRED);
            if (empty($cids) || !is_array($cids)) {
                $links = xarMod::apiFunc('categories', 'user', 'getlinks',
                                       array('iids' => array($objectid),
                                             'itemtype' => $itemtype,
                                             'modid' => $modid,
                                             'reverse' => 0));
                if (!empty($links) && is_array($links) && count($links) > 0) {
                    $cids = array_keys($links);
                } else {
                    $cids = array();
                }
            }
        }
    } else {
        $cids = $extrainfo['cids'];
    }
    // get all valid cids
    $seencid = array();
    foreach ($cids as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        if (empty($seencid[$cid])) {
            $seencid[$cid] = 1;
        } else {
            $seencid[$cid]++;
        }
    }

    $items = array();
    for ($n = 0; $n < $numcats; $n++) {
        if (!isset($mastercids[$n])) {
            break;
        }
        $item = array();
        $item['num'] = $n + 1;
        $item['select'] = xarMod::apiFunc('categories', 'visual', 'makeselect',
                                       array('cid' => $mastercids[$n],
                                             'multiple' => 1,
                                             'name_prefix' => 'modify_',
                                             'return_itself' => true,
                                             'select_itself' => true,
                                             'values' => &$seencid));
        $items[] = $item;
    }

    $labels = array();
    if ($numcats > 1) {
        $labels['categories'] = xarML('Categories');
    } else {
        $labels['categories'] = xarML('Category');
    }

    return xarTplModule('categories','admin','modifyhook',
                         array('labels' => $labels,
                               'numcats' => $numcats,
                               'items' => $items));
------------------------------- */

    // check if we're previewing some modified item
    if (!xarVarFetch('preview', 'isset', $data['preview'], NULL, XARVAR_DONT_SET)) {return;}

    return $data;
}

?>
