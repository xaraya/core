<?php
/**
 * Utility function to pass individual item links
 *
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

/**
 * Utility function to pass individual item links to whoever
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 * with
 *     string   $args['itemtype'] item type (optional)<br/>
 *     array<int> $args['itemids'] array of item ids to get
 * @return array<mixed> the itemlink(s) for the item(s).
 */
function blocks_userapi_getitemlinks(Array $args=array())
{
    extract($args);

    if (empty($itemtype)) {
        $itemtype = 3; // block instances
    }
    if (!empty($itemids) && is_array($itemids)) {
        $itemids = array_filter($itemids);
    }
    $itemlinks = array();

    if (xarSecurity::check('EditBlocks',0)) {
        $showurl = true;
    } else {
        $showurl = false;
    }

    switch ($itemtype)
    {
        case 1: // block types
            $param = array();
            if (!empty($itemids) && count($itemids) == 1) {
                $param['type_id'] = $itemids[0];
            }
            $types = xarMod::apiFunc('blocks','types','getitems',$param);
            if (empty($itemids)) {
                $itemids = array_keys($types);
            }
            foreach ($itemids as $itemid) {
                if (!isset($types[$itemid])) continue;
                $label = $types[$itemid]['module'] . '/' . $types[$itemid]['type'];
                $itemlinks[$itemid] = array('label' => xarVar::prepForDisplay($label),
                                            'title' => xarML('Modify Block Type'),
                                            'url'   => $showurl ? xarController::URL('blocks', 'admin', 'modify_type',
                                                                            array('type_id' => $itemid)) : '');
            }
            break;
        /*
        @TODO: refactor this for blockgroup blocks
        case 2: // block groups
            $param = array();
            if (!empty($itemids) && count($itemids) == 1) {
                $param['id'] = $itemids[0];
            }
            $groups = xarMod::apiFunc('blocks','user','getallgroups',$param);
            if (empty($itemids)) {
                $itemids = array_keys($groups);
            }
            foreach ($itemids as $itemid) {
                if (!isset($groups[$itemid])) continue;
                $label = $groups[$itemid]['name'];
                $itemlinks[$itemid] = array('label' => xarVar::prepForDisplay($label),
                                            'title' => xarML('View Block Group'),
                                            'url'   => $showurl ? xarController::URL('blocks', 'admin', 'view_groups',
                                                                            array('id' => $itemid)) : '');
            }
            break;
        */
        case 3: // block instances
        default:
            $param = array();
            if (!empty($itemids)) {
                $param['block_id'] = $itemids;
            }
            $instances = xarMod::apiFunc('blocks','instances','getitems',$param);
            if (empty($itemids)) {
                $itemids = array_keys($instances);
            }
            foreach ($itemids as $itemid) {
                if (!isset($instances[$itemid])) continue;
                $label = $instances[$itemid]['name'];
                $itemlinks[$itemid] = array('label' => xarVar::prepForDisplay($label),
                                            'title' => xarML('Modify Block Instance'),
                                            'url'   => $showurl ? xarController::URL('blocks', 'admin', 'modify_instance',
                                                                            array('block_id' => $itemid)) : '');
            }
            break;
    }

    return $itemlinks;
}
