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
	if(!xarSecurityCheck('EditBlock', 0, 'Instance')) {return;}
    $authid = xarSecGenAuthKey();

    // Get all block instances (whether they have group membership or not.
    $instances =& xarModAPIfunc('blocks', 'user', 'getall');

/*
        // TODO: JS in the template? We must get a better way to pass ML text to JavaScript functions.
        $block['javascript'] = "return xar_base_confirmLink(this, '" . xarML('Delete instance') . " $block[title] ?')";
        $block['deleteurl'] = xarModUrl('blocks', 'admin', 'delete_instance', array('bid' => $block['id'], 'authid' => $authid));
        $blocks[] = $block;
*/

    // Get current style.
    $data['selstyle']                               = xarModGetUserVar('blocks', 'selstyle');

    // Set default style if none selected.
    if (empty($data['selstyle'])){
        $data['selstyle'] = 'plain';
    }
    
    $data['authid'] = $authid;
    
    // Select vars for drop-down menus.
    $data['style']['plain']                         = xarML('Plain');
    $data['style']['compact']                       = xarML('Compact');

    // State descriptions.
    $data['state_desc'][0] = xarML('Hidden');
    $data['state_desc'][1] = xarML('Minimized');
    $data['state_desc'][2] = xarML('Maximized');

    $data['blocks'] = $instances;

    return $data;
}

?>
