<?php 
/**
 * File: $Id$
 *
 * Includes a file into a block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage Base Module
 * @author Patrick Kellum
*/

/**
 * Block init - holds security.
 */
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
         'func_update' => 'base_fincludeblock_update',
		 'allow_multiple' => true,
		 'form_content' => false,
		 'form_refresh' => false,
		 'show_preview' => true);
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
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
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_modify($blockinfo)
{
    if (!empty($blockinfo['url'])) {
        $url = $blockinfo['url'];
    } else {
        $url = '';
    }
    
    $content = xarTplBlock('base','fincludeAdmin', array('url' => $url));

    return $content;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_htmlblock_update($blockinfo)
{
    $vars['url'] = xarVarCleanFromInput('url');

    // Defaults
    if (empty($vars['url'])) {
        $vars['url'] = 'Error - No Url Specified';
    }
    
    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}
?>