<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
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
    xarSecAddSchema('base:Textblock', 'Block title::');
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
    if (!xarSecAuthAction(0,'base:Textblock', "$blockinfo[title]::", ACCESS_OVERVIEW)){
        return;
    }
    return $blockinfo;
}
?>