<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * view block instances
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_view_instances()
{
    // Security Check
    if (!xarSecurityCheck('EditBlocks', 0, 'Instance')) {return;}

    $data = array();

    if (!xarVarFetch('filter', 'str', $filter, "", XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('startnum', 'int', $startnum,   1,      XARVAR_NOT_REQUIRED)) {return;}

    // Get current style.
    $data['selstyle'] = xarModUserVars::get('blocks', 'selstyle');
    // Set default style if none selected.
    if (empty($data['selstyle'])){
        $data['selstyle'] = 'plain';
    }

    if ($data['selstyle'] == 'bygroup') {
        $order = 'group';
    } elseif ($data['selstyle'] == 'bytype') {
        $order = 'type';
    } else {
        $order = 'name';
    }

    $itemsperpage = xarModVars::get('blocks', 'items_per_page');
    $total = xarMod::apiFunc('blocks', 'user', 'count_instances',
        array('order' => $order, 'filter' => $filter));
    $instances = xarMod::apiFunc('blocks', 'user', 'getall',
        array('filter' => $filter, 'order' => $order, 'startnum' => $startnum, 'numitems' => $itemsperpage));

    $authid = xarSecGenAuthKey();
    // Create extra links and confirmation text.
    foreach ($instances as $index => $instance) {
        $instances[$index]['modifyurl'] = xarModUrl(
            'blocks', 'admin', 'modify_instance',
            array('bid' => $instance['bid'])
        );
        $instances[$index]['deleteurl'] = xarModUrl(
            'blocks', 'admin', 'delete_instance',
            array('bid' => $instance['bid'], 'authid' => $authid)
        );
        $instances[$index]['typeurl'] = xarModUrl(
            'blocks', 'admin', 'view_types',
            array('tid' => $instance['tid'])
        );
        if (isset($instance['groupid'])) {
            $instances[$index]['groupurl'] = xarModUrl(
                'blocks', 'admin', 'modify_instance',
                array('bid' => $instance['groupid'])
            );
        }
        $instances[$index]['deleteconfirm'] = xarML('Delete instance "#(1)"', addslashes($instance['name']));
    }

    $data['authid'] = $authid;
    // State descriptions.
    $data['state_desc'][0] = xarML('Hidden');
    $data['state_desc'][1] = xarML('Inactive');
    $data['state_desc'][2] = xarML('Visible');
    $data['blocks'] = $instances;
    $data['filter'] = $filter;
    $data['itemsperpage'] = $itemsperpage;
    $data['startnum'] = $startnum;
    $data['total'] = $total;

    return $data;
}
?>