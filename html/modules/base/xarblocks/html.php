<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Patrick Kellum
// Purpose of file: Display html content of the block
// ----------------------------------------------------------------------
/**
 * Block init - holds security.
 */
function base_htmlblock_init()
{
    // Security
    xarSecAddSchema('base:HTMLblock', 'Block title::');
}
/**
 * block information array
 */
function base_htmlblock_info()
{
    return array('text_type' => 'HTML',
		 'text_type_long' => 'HTML',
		 'module' => 'base',
		 'allow_multiple' => true,
		 'form_content' => true,
		 'form_refresh' => false,
		 'show_preview' => true);

}
/**
 * Display func.
 * @param $row array containing title,content
 */
function base_htmlblock_display($blockinfo)
{
    if (!xarSecAuthAction(0, 'base:HTMLblock', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
        return;
    }
    return $blockinfo;
}
?>