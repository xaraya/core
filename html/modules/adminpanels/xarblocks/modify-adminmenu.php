<?php
/**
 * Adminmenu block options handler.
 *
 * @copyright (C) 2005 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage adminpanels
 * @author Marcel van der Boom
 */


/**
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
    if (empty($vars['showlogout'])) {
        $vars['showlogout'] = 0;
    }
    
    $args['showlogout'] = $vars['showlogout'];
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
    
    $blockinfo['content'] = $vars;
    
    return $blockinfo;
}

?>