<?php
/**
 * Utility function to pass individual item links
 *
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * utility function to pass individual item links to whoever
 *
 * @param array    $args array of optional parameters<br/>
 *        string   $args['itemtype'] item type (optional)<br/>
 *        array    $args['itemids'] array of item ids to get
 * @return array the itemlink(s) for the item(s).
 */
function blocks_userapi_getitemlinks(Array $args=array())
{
    extract($args);

    if (empty($itemtype)) {
        $itemtype = 3; // block instances
    }
    $itemlinks = array();

    if (xarSecurityCheck('EditBlocks',0)) {
        $showurl = true;
    } else {
        $showurl = false;
    }

    switch ($itemtype)
    {
        case 1: // block types
            $param = array();
            if (!empty($itemids) && count($itemids) == 1) {
                $param['tid'] = $itemids[0];
            }
            $types = xarMod::apiFunc('blocks','user','getallblocktypes',$param);
            if (empty($itemids)) {
                $itemids = array_keys($types);
            }
            foreach ($itemids as $itemid) {
                if (!isset($types[$itemid])) continue;
                $label = $types[$itemid]['module'] . '/' . $types[$itemid]['type'];
                $itemlinks[$itemid] = array('label' => xarVarPrepForDisplay($label),
                                            'title' => xarML('View Block Type'),
                                            'url'   => $showurl ? xarModURL('blocks', 'admin', 'view_types',
                                                                            array('tid' => $itemid)) : '');
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
                $itemlinks[$itemid] = array('label' => xarVarPrepForDisplay($label),
                                            'title' => xarML('View Block Group'),
                                            'url'   => $showurl ? xarModURL('blocks', 'admin', 'view_groups',
                                                                            array('id' => $itemid)) : '');
            }
            break;
        */
        case 3: // block instances
        default:
            $param = array();
            if (!empty($itemids) && count($itemids) == 1) {
                $param['bid'] = $itemids[0];
            }
            $instances = xarMod::apiFunc('blocks','user','getall',$param);
            if (empty($itemids)) {
                $itemids = array_keys($instances);
            }
            foreach ($itemids as $itemid) {
                if (!isset($instances[$itemid])) continue;
                $label = $instances[$itemid]['name'];
                $itemlinks[$itemid] = array('label' => xarVarPrepForDisplay($label),
                                            'title' => xarML('Modify Block Instance'),
                                            'url'   => $showurl ? xarModURL('blocks', 'admin', 'modify_instance',
                                                                            array('bid' => $itemid)) : '');
            }
            break;
    }

    return $itemlinks;
}

?>