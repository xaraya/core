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
 * @param $args['bid'] the ID of the block to delete
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_delete_cacheinstance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) {
        $msg = xarML('Invalid parameter');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    // Security
    if (!xarSecurityCheck('DeleteBlock', 1, 'Block', "::$bid")) {return;}

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    if (!empty($xartable['cache_blocks'])) {
        $cacheblockstable = $xartable['cache_blocks'];
        $query = "DELETE FROM $cacheblockstable 
                  WHERE xar_bid = ?";
        $result =& $dbconn->Execute($query,array($bid));
        if (!$result) return;
    }
    return true;
}
?>