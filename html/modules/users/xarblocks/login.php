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
function users_loginblock_init()
{
    // Security
    xarSecAddSchema('users:Loginblock:', 'Block title::');
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
    if (!xarSecAuthAction(0,
                         'users:Loginblock:',
                         "$blockinfo[title]::",
                         ACCESS_READ)) {
        return;
    }

    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    // Display logout block if user is already logged in
    // e.g. when the login/logout block also contains a search box
    if (xarUserIsLoggedIn()) {
        if (!empty($vars['showlogout'])) {
            $args['name'] = xarUserGetVar('name');
            $args['search'] = 'Search';
            $blockinfo['content'] = xarTplBlock('users', 'logout', $args);
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

    $blockinfo['content'] = xarTplBlock('users', 'login', $args);
    
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
    $row[] = $output->Text(xarML('Show logout box when logged in'));
    $row[] = $output->FormCheckbox('showlogout',$vars['showlogout']);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddRow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Create row
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(xarML('Logout Title'));
    $row[] = $output->FormText('logouttitle',
                               xarVarPrepForDisplay($vars['logouttitle']),
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
