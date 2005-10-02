<?php
/**
 * User Info via block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/* 
 * User Info via block
 * @author Marco Canini
 */

/**
 * initialize the block
 */
function roles_userblock_init()
{
    return array(
        'nocache' => 1, // don't cache by default
        'pageshared' => 1, // share across pages
        'usershared' => 0, // don't share across users
        'cacheexpire' => null);
}

/**
 * info array
 */
function roles_userblock_info()
{
    return array(
        'text_type' => 'User',
        'text_type_long' => "User's Custom Box",
        'module' => 'roles',
        'allow_multiple' => false,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function roles_userblock_display($blockinfo)
{
    if ((xarUserIsLoggedIn()) && (xarUserGetVar('ublockon') == 1)) {
        $ublock = xarUserGetVar('ublock');
        if ($ublock === false) {
            $ublock = '';
        }
        $username = xarUserGetVar('name');
        $blockinfo['title'] = "". xarML('Menu For #(1)', $username);
        $blockinfo['content'] = $ublock;
        return $blockinfo;
    }
}
?>
