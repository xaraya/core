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

    // Display logout block if user is already logged in
    // e.g. when the login/logout block also contains a search box
    if (pnUserLoggedIn()) {
        if (!empty($vars['showlogout'])) {
            $args['name'] = pnUserGetVar('name');
            $blockinfo['content'] = pnTplBlock('users', 'logout', $args);
            if (!empty($vars['logouttitle'])) {
                $blockinfo['title'] = $vars['logouttitle'];
            }
            return $blockinfo;
        } else {
            return;
        }
    }

    // URL of this page
    // TODO - make this generic so that it works with all
    //        webservers - pnGetThisURL?
    //$args['return_url'] = pnServerGetVar('REQUEST_URI');
    // Get base URL
    $baseurl = pnServerGetBaseURL();
    $baseurl = preg_replace('#^(https?://[^/]+)/.*$#','\\1',$baseurl);
    $args['return_url'] = $baseurl . pnServerGetVar('REQUEST_URI');

    $blockinfo['content'] = pnTplBlock('users', 'login', $args);
    
    return $blockinfo;
}

// TODO - modify/update block settings
/**
 * modify block settings
 */
function users_loginblock_modify($blockinfo)
{
    // Create output object
    $output = new pnHTML();

    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['showlogout'])) {
        $vars['showlogout'] = 0;
    }
    if (empty($vars['logouttitle'])) {
        $vars['logouttitle'] = '';
    }

    // Create row
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnML('Show logout box when logged in'));
    $row[] = $output->FormCheckbox('showlogout',$vars['showlogout']);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddRow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Create row
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnML('Logout Title'));
    $row[] = $output->FormText('logouttitle',
                               pnVarPrepForDisplay($vars['logouttitle']),
                               15,
                               25);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddRow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);


    // Return output
    return $output->GetOutput();
}

/**
 * update block settings
 */
function users_loginblock_update($blockinfo)
{
    list($vars['showlogout'],
         $vars['logouttitle']) = pnVarCleanFromInput('showlogout',
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
