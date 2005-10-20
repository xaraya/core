<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

/**
 * Admin menu block option handler
 * Modify the instance configuration
 * @param $blockinfo array containing title,content
 */
function adminpanels_adminmenublock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }
    
    // Defaults
    if(empty($vars['showlogout'])) $vars['showlogout'] = 0;
    if(empty($vars['showmarker'])) $vars['showmarker'] = 0;
    if(empty($vars['menustyle']))  $vars['menustyle'] = xarModGetVar('adminpanels','menustyle');
    
    // Set the config values
    $args['showlogout'] = $vars['showlogout'];
    $args['menustyle']  = $vars['menustyle'];
    
    // Set the template data we need
    $sortorder = array('byname' => xarML('By Name'),
                       'bycat'  => xarML('By Category'));
    $args['sortorder'] = $sortorder;
    $args['blockid'] = $blockinfo['bid'];
    return $args;
}

/**
 * Update the instance configuration
 * @param $blockinfo array containing title,content
 */
function adminpanels_adminmenublock_update($blockinfo)
{
    if (!xarVarFetch('showlogout', 'int:0:1', $vars['showlogout'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('menustyle' , 'str::'  , $vars['menustyle'] , xarModGetVar('adminpanels','menustyle'), XARVAR_NOT_REQUIRED)) return;
    
    $blockinfo['content'] = $vars;
    
    return $blockinfo;
}

?>
