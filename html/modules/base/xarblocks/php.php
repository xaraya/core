<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
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
    xarSecAddSchema('base:PHPblock', 'Block title::');
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
    if (!xarSecAuthAction(0, 'base:PHPblock', "$blockinfo[title]::", ACCESS_READ)) {
        return;
    }
    ob_start();
    print eval($blockinfo['content']);
    $blockinfo['content'] = ob_get_contents();
    ob_end_clean();
    return $blockinfo;
}
?>