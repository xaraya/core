<?php
/**
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

/**
 * Return table name definitions to Xaraya.
 * 
 * This function is called internally by the core whenever the module is
 * loaded. It adds the table names to a globally accessible array
 * 
 * @param void N/A
 * @return array Registered table names to a globally accessibl array
 */
function blocks_xartables()
{
    $prefix = xarDB::getPrefix();
    //$tables['userblocks']         = $prefix . '_userblocks';
    $tables['block_instances']        = $prefix . '_block_instances';
    //$tables['block_group_instances']  = $prefix . '_block_group_instances';
    $tables['block_types']            = $prefix . '_block_types';
    //$tables['cache_blocks']           = $prefix . '_cache_blocks';
    
    return $tables;
}
?>