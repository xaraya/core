<?php
/**
 * Delete a cache block instance
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * delete a cache block
 * 
 * @param $args['bid'] the ID of the block to delete
 * @return bool true on success, false on failure
 */
function blocks_adminapi_delete_cacheinstance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if(!isset($bid)) throw new EmptyParameterException('bid');
    if(!is_numeric($bid)) throw new BadParameterException($bid);
    
    // Security
    if (!xarSecurityCheck('DeleteBlock', 1, 'Block', "::$bid")) {return;}

    // Delete the cached block instance, if any
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    if (!empty($xartable['cache_blocks'])) {
        $cacheblockstable = $xartable['cache_blocks'];
        $query = "DELETE FROM $cacheblockstable WHERE xar_bid=?";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->ExecuteUpdate(array($bid));
    }
    return true;
}
?>