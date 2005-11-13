<?php
/**
 * Delete a block instance
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * delete a block
 * @author Jim McDonald, Paul Rosania
 * @param $args['bid'] the ID of the block to delete
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_delete_instance($args)
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
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "DELETE FROM $block_group_instances_table
              WHERE xar_instance_id = " . $bid;
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    $query = "DELETE FROM $block_instances_table
              WHERE xar_id = " . $bid;
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    xarModAPIFunc('blocks', 'admin', 'resequence');

    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarModCallHooks('item', 'delete', $bid, $args);

    return true;
}

?>