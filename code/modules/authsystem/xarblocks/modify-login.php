<?php
/**
 * Modify Function for the Blocks Admin
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * Modify Function for the Blocks Admin
 * @author Jim McDonald
 * @param $blockinfo array containing title,content
 */
function authsystem_loginblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (!$vars['showlogout']) {
        $vars['showlogout'] = false;
    }
    if (is_null($vars['logouttitle'])) {
        $vars['logouttitle'] = '';
    }

    $args['showlogout'] = $vars['showlogout'];
    $args['logouttitle'] = $vars['logouttitle'];

    $args['blockid'] = $blockinfo['bid'];
    return $args;

}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function authsystem_loginblock_update($blockinfo)
{
    if (!xarVarFetch('showlogout', 'checkbox', $vars['showlogout'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('logouttitle', 'str', $vars['logouttitle'], '', XARVAR_NOT_REQUIRED)) return;

    $blockinfo['content'] = $vars;

    return $blockinfo;
}

?>