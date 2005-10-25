<?php
/**
 * Initialisation and display of the form block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * initialise block
 */
function dynamicdata_formblock_init()
{
    return array('objectid' => null);
}

/**
 * get information on block
 */
function dynamicdata_formblock_info()
{
    // Values
    return array('text_type' => 'form',
                 'module' => 'dynamicdata',
                 'text_type_long' => 'Show dynamic data form',
                 'allow_multiple' => true,
                 'form_content' => false,
                 'form_refresh' => false,
                 'show_preview' => true);
}

/**
 * display block
 */
function dynamicdata_formblock_display($blockinfo)
{
    // Security check
    if(!xarSecurityCheck('ReadDynamicDataBlock',0,'Block',"$blockinfo[title]:All:All")) return;

    // Get variables from content block
    if (is_string($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Populate block info and pass to theme
    if (!empty($vars['objectid'])) {
        $objectinfo = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                    $vars);
        if (!empty($objectinfo)) {
            if (!xarSecurityCheck('AddDynamicDataItem',0,'Item',"$objectinfo[moduleid]:$objectinfo[itemtype]:All")) return;
            $blockinfo['content'] = $objectinfo;
            return $blockinfo;
        }
    }
}

/**
 * built-in block help/information system.
 */
function dynamicdata_formblock_help()
{
    // No information yet.
    return '';
}
?>
