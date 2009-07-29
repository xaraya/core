<?php
/**
 * Delete a block group
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * delete a group
 * @author Jim McDonald, Paul Rosania
 * @param $args['id'] the ID of the block group to delete
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_delete_group($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($id) || !is_numeric($id)) throw new BadParameterException('id');

    // Security
    if (!xarSecurityCheck('DeleteBlock', 1, 'Block', "::$id")) {return;}

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $block_groups_table = $xartable['block_groups'];
    $block_group_instances_table = $xartable['block_group_instances'];

    // Delete group-instance links
    try {
        $dbconn->begin();
        $query = "DELETE FROM $block_group_instances_table  WHERE group_id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($id));

        // Delete block group definition
        $query = "DELETE FROM $block_groups_table WHERE id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($id));
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}

?>
