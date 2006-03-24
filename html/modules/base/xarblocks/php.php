<?php
/**
 * PHP block
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * init func
 * @author Patrick Kellum
 */
function base_phpblock_init()
{
    return array();
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
 * Display func.
 * @param $blockinfo array containing title,content
 */
function base_phpblock_display($blockinfo)
{
    // Security Check
    if(!xarSecurityCheck('ViewBaseBlocks',0,'Block',"php:$blockinfo[title]:$blockinfo[bid]")) return;

    ob_start();
    print eval($blockinfo['content']);
    $blockinfo['content'] = ob_get_contents();
    ob_end_clean();

    if (empty($blockinfo['content'])){
        $blockinfo['content'] = xarML('Content is empty');
    }

    return $blockinfo;
}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_phpblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    $vars['blockid'] = $blockinfo['bid'];
    return $vars;
}
?>