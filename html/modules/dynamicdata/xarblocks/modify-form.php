<?php
/**
 * Modify the form block
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
 * modify block settings
 */
function dynamicdata_formblock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['objectid'])) {
        $vars['objectid'] = 0;
    }

    $vars['blockid'] = $blockinfo['bid'];

    // Return output
    return $vars;
}

/**
 * update block settings
 */
function dynamicdata_formblock_update($blockinfo)
{
    if (!xarVarFetch('objectid', 'id', $vars['objectid'], 0, XARVAR_NOT_REQUIRED)) {return;}

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}
?>
