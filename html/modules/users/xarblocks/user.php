<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Everyone
// Purpose of file: Users Block
// ----------------------------------------------------------------------

/**
 * init	
 */
function users_userblock_init()
{
    xarSecAddSchema(0, 'users:userblock', 'Block title');
}

/**
 * info array
 */
function users_userblock_info()
{
    return array('text_type' => 'User',
		 'text_type_long' => "User's Custom Box",
		 'module' => 'users',
		 'allow_multiple' => false,
		 'form_content' => false,
		 'form_refresh' => false,
		 'show_preview' => true);
}

/**
 * Display func
 */
function users_userblock_display($blockinfo)
{
    if ((xarUserIsLoggedIn()) && (xarUserGetVar('ublockon') == 1)) {
        $ublock = xarUserGetVar('ublock');
        if ($ublock === false) {
            $ublock = '';
        }
        $username = xarUserGetVar('name');
        $blockinfo['title'] = _MENUFOR." ".xarVarPrepForDisplay($username)."";
        $blockinfo['content'] = $ublock;
        return $blockinfo;
    }
}
?>