<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: user login
// ----------------------------------------------------------------------

/**
 * initialise block
 */
function roles_loginblock_init()
{
    // Security
    xarSecAddSchema('roles:Loginblock:', 'Block title::');
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
 * display block
 */
function roles_loginblock_display($blockinfo)
{
// Security Check
	if(!xarSecurityCheck('ViewLogin',1,'Block','$blockinfo[title]:All:All','All')) return;

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    // Display logout block if user is already logged in
    // e.g. when the login/logout block also contains a search box
    if (xarUserIsLoggedIn()) {
        if (!empty($vars['showlogout'])) {
            $args['name'] = xarUserGetVar('name');
            $args['search'] = 'Search';
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
    $args['return_url'] = xarServerGetCurrentURL();

    $blockinfo['content'] = xarTplBlock('roles', 'login', $args);

    return $blockinfo;
}

// TODO - modify/update block settings
/**
 * modify block settings
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

    $content = xarTplBlock('roles', 'loginAdmin', $args);

    return $content;
}

/**
 * update block settings
 */
function roles_loginblock_update($blockinfo)
{
    list($vars['showlogout'],
         $vars['logouttitle']) = xarVarCleanFromInput('showlogout',
                                                    'logouttitle');

    // Defaults
    if (empty($vars['showlogout'])) {
        $vars['showlogout'] = 0;
    }
    if (empty($vars['logouttitle'])) {
        $vars['logouttitle'] = '';
    }

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}

?>