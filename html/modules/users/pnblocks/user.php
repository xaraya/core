<?php // File: $Id$ $Name$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
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
// Original Author of file: Everyone
// Purpose of file: Users Block
// ----------------------------------------------------------------------

/**
 * init	
 */
function users_userblock_init()
{
    pnSecAddSchema(0, 'users:userblock', 'Block title');
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
    if ((pnUserLoggedIn()) && (pnUserGetVar('ublockon') == 1)) {
        $ublock = pnUserGetVar('ublock');
        if ($ublock === false) {
            $ublock = '';
        }
        $username = pnUserGetVar('name');
        $blockinfo['title'] = _MENUFOR." ".pnVarPrepForDisplay($username)."";
        $blockinfo['content'] = $ublock;
        return $blockinfo;
    }
}
?>