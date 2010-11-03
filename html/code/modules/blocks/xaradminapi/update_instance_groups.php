<?php
/**
 * Update the group details for a block instance
 * @package modules
 * @subpackage blocks module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * update the group details for a block instance
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @param $args['bid'] the ID of the block to update
 * @param $args['groups'] array of group memberships
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_update_instance_groups($args)
{
    $template = null;
    extract($args);

    // The group instances are updated according to the
    // $groups parameter.
    // $groups is an array of _current_ group memberships.
    // Each group membership is an array:
    // 'id' - group ID
    // 'template' - the over-ride template for this block group instance
    // This function will add, update or delete group memberships for
    // the block instance to leave the group membership state as defined
    // by the $groups array.

    if (!isset($groups) || !is_array($groups) || !isset($bid) || !is_numeric($bid)) {
        return;
    }

    $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));

    $current = $instance['groups'];

    // Key the new groups on the id for convenience
    $newgroups = array();
    foreach($groups as $group) {
        if (!isset($group['template'])) {
            $group['template'] = null;
        }
        $newgroups[$group['id']] = $group;
    }

    $allgroups = xarMod::apiFunc('blocks', 'user', 'getall', array('type' => 'blockgroup'));
    $toremove = array();
    $toupdate = array();
    $toinsert = array();
    foreach ($allgroups as $id => $group) {
        // block to be removed from this group
        if (!isset($newgroups[$id]) && isset($current[$id])) {
            $toremove[] = $group;
        }
        // block to be added to this group
        elseif (isset($newgroups[$id]) && !isset($current[$id])) {
            $toinsert[] = $group;
        }
        // block already belongs to group, update if necessary
        elseif (isset($newgroups[$id]) && isset($current[$id])
            && $newgroups[$id]['template'] != $current[$id]['group_inst_template'])
        {
            $toupdate[] = $group;
        }
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $group_instances = $xartable['block_group_instances'];

    if (!empty($toremove)) {
        // Prepare the queries we need in the loop
        $delQuery = "DELETE FROM $group_instances WHERE group_id = ? AND instance_id = ?";
        $delStmt  = $dbconn->prepareStatement($delQuery);
        foreach ($toremove as $block) {
            $bind = array($block['bid'], $bid);
            $delStmt->executeUpdate($bind);
        }
    }

    if (!empty($toinsert)) {
        $insQuery = "INSERT INTO $group_instances
                    (group_id, instance_id, position, template)
                    VALUES (?,?,?,?)";
        $insStmt  = $dbconn->prepareStatement($insQuery);
        foreach ($toinsert as $block) {
            $id = $block['bid'];
            $bind = array($block['bid'], $bid, 0, $newgroups[$id]['template']);
            $insStmt->executeUpdate($bind);
        }
    }

    if (!empty($toupdate)) {
        $updQuery = "UPDATE $group_instances
                     SET template = ?
                     WHERE id = ?";
        $updStmt  = $dbconn->prepareStatement($updQuery);
        foreach ($toupdate as $block) {
            $id = $block['bid'];
            $bind = array($newgroups[$id]['template'], $block['bid']);
            $updStmt->executeUpdate($bind);
        }
    }

    // Resequence the position values, since we may have changed the existing values.
    // Span the resequence across all groups, since any number of groups could have
    // been affected.
    xarMod::apiFunc('blocks', 'admin', 'resequence');

    return true;

}
?>