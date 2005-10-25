<?php
/**
 * Activate a block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * activate a block
 * @author Jim McDonald, Paul Rosania
 * @param $args['bid'] the ID of the block to activate
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_activate($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) {
        $msg = xarML('Wrong arguments for blocks_adminapi_activate');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Security
    if(!xarSecurityCheck('CommentBlock',1,'Block',"::$bid")) {return;}

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $blockstable = $xartable['block_instances'];

    // Deactivate
    $query = "UPDATE $blockstable
            SET xar_state = 2
            WHERE xar_id = " . $bid;
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    return true;
}

?>
