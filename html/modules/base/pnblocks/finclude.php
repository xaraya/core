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
// Original Author of file: Patrick Kellum
// Purpose of file: Include a file
// ----------------------------------------------------------------------

function base_fincludeblock_init()
{
    pnSecAddSchema('base:Includeblock', 'Block title::');

}
/**
 * Block info array
 */
function base_fincludeblock_info()
{
    return array('text_type' => 'finclude',
		 'text_type_long' => 'Simple File Include',
		 'module' => 'base',
		 'allow_multiple' => true,
		 'form_content' => false,
		 'form_refresh' => false,
		 'show_preview' => true);
}
/**
 * Display func.
 */
function base_fincludeblock_display($blockinfo)
{
    if (!file_exists($blockinfo['url'])) {
        return;
    }
    $blockinfo['content'] = implode(file($blockinfo['url']), '');
    return $blockinfo;
}
/**
 * Edit func
 */
function base_fincludeblock_modify($blockinfo)
{
    return '<tr><td class="pn-normal">File:</td><td><input type="text" name="url" size="30" maxlength="255" value="'.$blockinfo['url'].'" /></td></tr>';
}
?>