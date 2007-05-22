<?php
/**
 * Update attributes of a Block
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * update attributes of a block group
 *
 * @author Jim McDonald, Paul Rosania
 * @param $args['id'] the ID of the group to update (deprec)
 * @param $args['gid'] the ID of the group to update
 * @param $args['name'] the new name of the group
 * @param $args['template'] the new default template of the group
 * @param $args['instance_order'] the new instance sequence (array of bid)
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_update_group($args)
{
    // Get arguments from argument array
    extract($args);

    if (!empty($id)) {
        // Legacy.
        $gid = $id;
    }

    // Security.
    // FIXME: this doesn't seem right - it is a block group, not a block instance here.
    if (!xarSecurityCheck('EditBlock', 1, 'Block', "$name::$gid")) {return;}

    if (!is_numeric($id)) {return;}

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $block_groups_table =& $xartable['block_groups'];
    $block_group_instances_table =& $xartable['block_group_instances'];

    $query = "UPDATE $block_groups_table
              SET name = ?, template = ?
              WHERE id = ?";
    $dbconn->Execute($query, array($name, $template, $gid));

    if (!empty($instance_order)) {
        $position = 1;
        foreach ($instance_order as $instance_id) {
            $query = "UPDATE $block_group_instances_table
                      SET position = ?
                      WHERE instance_id = ? AND
                            group_id = ? AND
                            position <> ?";
            if (is_numeric($instance_id)) {
                $dbconn->Execute($query, array($position, $instance_id, $gid, $position));
            }

            $position += 1;
        }

        // Do a resequence tidy-up, in case the instance list passed in was not complete.
        // Limit the reorder to just this group to avoid updating more than is necessary.
        xarModAPIfunc('blocks', 'admin', 'resequence', array('gid' => $gid));
    }

    return true;
}

?>
