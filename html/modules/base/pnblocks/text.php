<?php // $Id$ $Name$
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
// Purpose of file: Display the text content of the block
// ----------------------------------------------------------------------
/**
 * init func
 */
function base_textblock_init()
{
    // Security
    pnSecAddSchema('base:Textblock', 'Block title::');
}
/**
 * Block info array
 */
function base_textblock_info()
{
    return array('text_type' => 'Text',
		 'text_type_long' => 'Plain Text',
		 'module' => 'base',
		 'allow_multiple' => true,
		 'form_content' => true,
		 'form_refresh' => false,
		 'show_preview' => true);
}
/**
 * Display func.
 */
function base_textblock_display($blockinfo)
{
    if (!pnSecAuthAction(0,'base:Textblock', "$blockinfo[title]::", ACCESS_OVERVIEW)){
        return;
    }
    return $blockinfo;
}
?>