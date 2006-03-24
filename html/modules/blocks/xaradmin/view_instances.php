<?php
/**
 * View block instances
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
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
    if (!xarVarFetch('filter', 'str', $filter, "", XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('startat', 'int', $startat,   1,      XARVAR_NOT_REQUIRED)) {return;}

// Security Check
    if (!xarSecurityCheck('EditBlock', 0, 'Instance')) {return;}
    $authid = xarSecGenAuthKey();

    // Get all block instances (whether they have group membership or not.
    // CHECKME: & removed below for php 4.4.
    $rowstodo = xarModGetVar('blocks','itemsperpage');
    // Need to find a better way to do this without breaking the API
    $instances = xarModAPIfunc('blocks', 'user', 'getall', array('filter' => $filter,
                                                                 'order' => 'name'));
    $total = count($instances);
    $instances = xarModAPIfunc('blocks', 'user', 'getall', array('filter' => $filter,
                                                                 'order' => 'name',
                                                                 'rowstodo' => $rowstodo,
                                                                 'startat' => $startat));
    // Get current style.
    $data['selstyle'] = xarModGetUserVar('blocks', 'selstyle');

    // Create extra links and confirmation text.
    foreach ($instances as $index => $instance) {
        $instances[$index]['deleteurl'] = xarModUrl(
            'blocks', 'admin', 'delete_instance',
            array('bid' => $instance['bid'], 'authid' => $authid)
        );
        $instances[$index]['typeurl'] = xarModUrl(
            'blocks', 'admin', 'view_types',
            array('tid' => $instance['tid'])
        );
        $instances[$index]['deleteconfirm'] = xarML('Delete instance "#(1)"', addslashes($instance['name']));
    }

    // Set default style if none selected.
    if (empty($data['selstyle'])){
        $data['selstyle'] = 'plain';
    }

    $data['authid'] = $authid;
    // Item filter and pager
    $data['filter'] = $filter;
    $data['pager'] = xarTplGetPager($startat,
                            $total,
                            xarModURL('blocks', 'admin', 'view_instances',array('startat' => '%%')),
                            $rowstodo);

    // Select vars for drop-down menus.
    $data['style']['plain'] = xarML('Plain');
    $data['style']['compact'] = xarML('Compact');

    // State descriptions.
    $data['state_desc'][0] = xarML('Hidden');
    $data['state_desc'][1] = xarML('Minimized');
    $data['state_desc'][2] = xarML('Maximized');

    $data['blocks'] = $instances;

    return $data;
}

?>