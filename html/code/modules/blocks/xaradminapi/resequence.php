<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * resequence a blocks table
 * @author Jim McDonald
 * @author Paul Rosania
 * @return boolean
 */
function blocks_adminapi_resequence($args)
{
    extract($args);

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $bind = array();
    $where_clause = '';

    if (!empty($id) && is_numeric($id)) {
        $where_clause .= ' WHERE group_id = ?';
        $bind[] = $id;
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
        list($link_id, $id, $old_position) = $qresult->fields;

        // Reset sequence number if we've changed the group we're sorting
        if ($last_gid != $id) {
            $last_gid = $id;
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
