<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Modify configuration for a module - hook for ('module','modifyconfig','GUI')
 * 
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return string Returns display string
 * @throws BadParameterException Thrown if modid was not found
 */
function categories_admin_modifyconfighook($args)
{
    extract($args);

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
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module name', 'admin', 'modifyconfighook', 'categories');
        throw new BadParameterException(null, $msg);
    }

/* ----------------------- TODO Remove
    // see what we have to show here
    if (empty($extrainfo['number_of_categories'])) {
        // try to get number of categories from current settings
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
    }

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
    // get all valid master cids for this module
    // Note : a module might have the same master cid twice (just in case...)
    $cleancids = array();
    foreach ($mastercids as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        // preserve order of root categories if possible - do not use this for multi-select !
        $cleancids[] = $cid;
    }

    $items = array();
    for ($n = 0; $n < $numcats; $n++) {
        $item = array();
        $item['num'] = $n + 1;
        // preserve order of root categories if possible - do not use this for multi-select !
        if (isset($cleancids[$n])) {
            $seencid = array($cleancids[$n] => 1);
        } else {
            $seencid = array();
        }
        // TODO: improve memory usage
        // limit to some reasonable depth for now
        $item['select'] = xarMod::apiFunc('categories', 'visual', 'makeselect',
                                       array('values' => &$seencid,
                                             'name_prefix' => 'config_',
                                             'maximum_depth' => 4,
                                             'show_edit' => true));
        $items[] = $item;
    }
    unset($item);
-----------------------------------*/
    if(xarSecurityCheck('AddCategories',0)) {
        $newcat = xarML('new');
    } else {
        $newcat = '';
    }

    $data = array();
    $data['newcat'] = $newcat;
//    $data['numcats'] = $numcats;
//    $data['items'] = $items;
    $data['modname'] = $modname;
    $data['itemtype'] = $extrainfo['itemtype'];

    return xarTplModule('categories','admin','modifyconfighook', $data);
}

?>
