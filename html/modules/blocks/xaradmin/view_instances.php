<?php
/** 
 * File: $Id$
 *
 * View block instances
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * view block instances
 */
function blocks_admin_view_instances()
{
// Security Check
    if (!xarSecurityCheck('EditBlock', 0, 'Instance')) {return;}
    $authid = xarSecGenAuthKey();

    // Get all block instances (whether they have group membership or not.
    // CHECKME: & removed below for php 4.4.
    $instances = xarModAPIfunc('blocks', 'user', 'getall', array('order' => 'name'));

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
