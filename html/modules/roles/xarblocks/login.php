<?php
/**
 * File: $Id$
 *
 * Login via a block.
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Jim McDonald
*/

/**
 * initialise block
 */
function roles_loginblock_init()
{
    return true;
}

/**
 * get information on block
 */
function roles_loginblock_info()
{
    return array('text_type' => 'Login',
                 'module' => 'roles',
                 'text_type_long' => 'User login');
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function roles_loginblock_display($blockinfo)
{
// Security Check
    if(!xarSecurityCheck('ViewLogin',1,'Block',"All:" . $blockinfo['title'] . ":All",'All')) return;

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    // Display logout block if user is already logged in
    // e.g. when the login/logout block also contains a search box
    if (xarUserIsLoggedIn()) {
        if (!empty($vars['showlogout'])) {
            $args['name'] = xarUserGetVar('name');
            $args['blockid'] = $blockinfo['bid'];
            $blockinfo['content'] = xarTplBlock('roles', 'logout', $args);
            if (!empty($vars['logouttitle'])) {
                $blockinfo['title'] = $vars['logouttitle'];
            }
            return $blockinfo;
        } else {
            return;
        }
    }

    // URL of this page
    $args['return_url'] = preg_replace('/&/', "&amp;$1", xarServerGetCurrentURL());
    $args['signinlabel']= xarML('Sign in');
    $args['blockid'] = $blockinfo['bid'];
    if (empty($blockinfo['template'])) {
        $template = 'login';
    } else {
        $template = $blockinfo['template'];
    }
    $blockinfo['content'] = xarTplBlock('roles', $template, $args);

    return $blockinfo;
}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function roles_loginblock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['showlogout'])) {
        $args['showlogout'] = 0;
    }
    if (empty($vars['logouttitle'])) {
        $args['logouttitle'] = '';
    }

    $args['showlogout'] = $vars['showlogout'];
    $args['logouttitle'] = $vars['logouttitle'];

    $args['blockid'] = $blockinfo['bid'];
    $content = xarTplBlock('roles', 'loginAdmin', $args);

    return $content;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function roles_loginblock_update($blockinfo)
{
    if (!xarVarFetch('showlogout', 'notempty', $vars['showlogout'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('logouttitle', 'notempty', $vars['logouttitle'], '', XARVAR_NOT_REQUIRED)) return;

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}

?>