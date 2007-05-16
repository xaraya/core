<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage blocks
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Return table names back to Xaraya
 */
function blocks_xartables()
{
    $tables = array();

    $prefix = xarDB::getPrefix();

    $tables['userblocks']   = $prefix . '_userblocks';
    $tables['block_types']  = $prefix . '_block_types';
    $tables['cache_blocks'] = $prefix . '_cache_blocks';

    return $tables;
}
?>
