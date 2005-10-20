<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * deactivate a block
 * @author Jim McDonald, Paul Rosania
 * @param $args['bid'] the ID of the block to deactivate
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_deactivate($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid)) {
        xarSessionSetVar('errormsg', _MODARGSERROR);
        return false;
    }

    // Security
    if(!xarSecurityCheck('EditBlock',1,'Block',"::$bid")) return;

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $blockstable = $xartable['block_instances'];

    // Deactivate
    $query = "UPDATE $blockstable SET xar_state = ?  WHERE xar_id = ?";
    $result =& $dbconn->Execute($query,array(0, $bid));
    if (!$result) return;

    return true;
}

?>
