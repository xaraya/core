<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Patrick Kellum
// Purpose of file: Include a file
// ----------------------------------------------------------------------

function base_fincludeblock_init()
{
    xarSecAddSchema('base:Includeblock', 'Block title::');

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
    // Return information to BlockLayout
    return array();
}
?>
