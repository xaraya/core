<?php // File: $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2002 by the Post-Nuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: user login
// ----------------------------------------------------------------------

/**
 * initialise block
 */
function users_loginblock_init()
{
    // Security
    pnSecAddSchema('users:Loginblock:', 'Block title::');
}

/**
 * get information on block
 */
function users_loginblock_info()
{
    return array('text_type' => 'Login',
                 'module' => 'users',
                 'text_type_long' => 'User login');
}

/**
 * display block
 */
function users_loginblock_display($blockinfo)
{
    // Security check
    if (!pnSecAuthAction(0,
                         'users:Loginblock:',
                         "$blockinfo[title]::",
                         ACCESS_READ)) {
        return;
    }

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    // Don't display if user is already logged in
    if (pnUserLoggedIn()) {
        return;
    }

    // URL of this page
    // TODO - make this generic so that it works with all
    //        webservers - pnGetThisURL?
    $args['return_url'] = pnServerGetVar('REQUEST_URI');

    $blockinfo['content'] = pnTplBlock('users', 'login', $args);
    
    return $blockinfo;
}

// TODO - modify/update block settings
?>
