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
// Purpose of file: Execute very simple PHP code
// ----------------------------------------------------------------------
/**
 * init func
 */
function base_phpblock_init()
{
    // Security
    pnSecAddSchema('base:PHPblock', 'Block title::');
}
/**
 * Block info array
 */
function base_phpblock_info()
{
    return array('text_type' => 'PHP',
		 'text_type_long' => 'PHP Script',
		 'module' => 'base',	
		 'allow_multiple' => true,
		 'form_content' => true,
		 'form_refresh' => false,
		 'show_preview' => true);
}
/**
 * display func
 */
function base_phpblock_display($blockinfo)
{
    if (!pnSecAuthAction(0, 'base:PHPblock', "$blockinfo[title]::", ACCESS_READ)) {
        return;
    }
    ob_start();
    print eval($blockinfo['content']);
    $blockinfo['content'] = ob_get_contents();
    ob_end_clean();
    return $blockinfo;
}
?>