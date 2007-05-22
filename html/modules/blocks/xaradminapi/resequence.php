<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * resequence a blocks table
 * @author Jim McDonald, Paul Rosania
 * @return bool
 */
function blocks_adminapi_resequence($args)
{
    extract($args);

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $bind = array();
    $where_clause = '';

    if (!empty($gid) && is_numeric($gid)) {
        $where_clause .= ' WHERE group_id = ?';
        $bind[] = $gid;
    }

    $block_group_instances_table =& $xartable['block_group_instances'];

    // Get the information
    $query = "SELECT id, group_id, position
              FROM $block_group_instances_table
              $where_clause
              ORDER BY group_id, position, id";
    $stmt = $dbconn->prepareStatement($query);
    $qresult = $stmt->executeQuery($bind);

    // Prepare the update query to be used in the loop outside of it.
    $upquery = "UPDATE $block_group_instances_table
                SET position = ? WHERE id = ? AND position <> ?";
    $upstmt  = $dbconn->prepareStatement($upquery);

    $last_gid = null;
    // Fix sequence numbers
    while ($qresult->next()) {
        list($link_id, $gid, $old_position) = $qresult->fields;

        // Reset sequence number if we've changed the group we're sorting
        if ($last_gid != $gid) {
            $last_gid = $gid;
            $position = 1;
        }
        if ($position != $old_position) {
            $bind = array($position, $link_id, $position);
            $upstmt->executeUpdate($bind);
        }
        $position++;
    }
    $qresult->close();

    return true;
}

?>
