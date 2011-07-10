<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Return table name definitions to Xaraya.
 * This function is called internally by the core whenever the module is
 * loaded. It adds the table names to a globally accessible array
 * @return array the registered tables of this module
 */
function blocks_xartables()
{
    $prefix = xarDB::getPrefix();
    $tables['userblocks']   = $prefix . '_userblocks';
    $tables['block_types']  = $prefix . '_block_types';
    $tables['cache_blocks'] = $prefix . '_cache_blocks';
    return $tables;
}
?>