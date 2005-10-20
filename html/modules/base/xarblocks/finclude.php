<?php
/**
 * File: $Id: s.finclude.php 1.23 03/07/13 11:22:54+02:00 marcel@hsdev.com $
 *
 * Includes a file into a block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
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
    return array(
        'url' => 'http://www.example.com/',
        'nocache' => 0, // cache by default
        'pageshared' => 1, // share across pages here
        'usershared' => 1, // and for group members
        'cacheexpire' => null
    );
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
    // Security Check
    if (!xarSecurityCheck('ViewBaseBlocks',0,'Block',"finclude:$blockinfo[title]:$blockinfo[bid]")) {return;}

    if (!is_array($blockinfo['content'])) {
        $blockinfo['content'] = unserialize($blockinfo['content']);
    } else {
        $blockinfo['content'] = $blockinfo['content'];
    }

    if (empty($blockinfo['content']['url'])){
        $blockinfo['content'] = xarML('Block has no file defined to include');
    } else {
        $blockinfo['url'] = $blockinfo['content']['url'];
        if (!file_exists($blockinfo['url'])) {
            $blockinfo['content'] = xarML('Warning: File to include does not exist. Check file definition in finclude block instance.');
        } else {
            $blockinfo['content'] = implode(file($blockinfo['url']), '');
        }
    }

    return $blockinfo;
}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_modify($blockinfo)
{
    if (!empty($blockinfo['url'])) {
        $args['url'] = $blockinfo['url'];
    } else {
        $args['url'] = '';
    }
    $args['blockid'] = $blockinfo['bid'];

    return $args;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_update($blockinfo)
{
    $vars = array();
    if (!xarVarFetch('url', 'isset', $vars['url'], xarML('Error - No Url Specified'), XARVAR_DONT_SET)) {return;}

    $blockinfo['content'] = $vars;
    return $blockinfo;
}

?>
