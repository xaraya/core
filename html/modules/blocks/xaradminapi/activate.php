<?php
/** 
 * File: $Id$
 *
 * Activate a block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * activate a block
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
        $msg = xarML('Invalid Parameter Count', join(', ', $invalid), 'admin', 'create', 'blocks');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Security
	if(!xarSecurityCheck('EditBlock',1,'Block',"::$bid")) {return;}

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $blockstable = $xartable['blocks'];

    // Deactivate
    $query = "UPDATE $blockstable
            SET xar_active = 1
            WHERE xar_bid = " . $bid;
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    return true;
}

?>
