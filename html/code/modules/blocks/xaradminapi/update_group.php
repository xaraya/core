<?php
/**
 * Update attributes of a Block
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
 * update ordering of a block group
 *
 * @TODO: This function is used solely by the blockgroup block
 * consider moving it to that blocks updateorder() method
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @param $args['id'] the ID of the group to update
 * @param $args['instance_order'] the new instance sequence (array of bid)
 * @return boolean true on success, false on failure
 */
function blocks_adminapi_update_group(Array $args=array())
{
    // Get arguments from argument array
    $template = null;
    extract($args);

    if (!empty($gid)) {
        // Legacy.
        $id = $gid;
    }

    if (!is_numeric($id)) {return;}

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $block_group_instances_table =& $xartable['block_group_instances'];

    if (!empty($instance_order)) {
        $position = 1;
        foreach ($instance_order as $instance_id) {
            $query = "UPDATE $block_group_instances_table
                      SET position = ?
                      WHERE instance_id = ? AND
                            group_id = ? AND
                            position <> ?";
            if (is_numeric($instance_id)) {
                $dbconn->Execute($query, array($position, $instance_id, $id, $position));
            }

            $position += 1;
        }

        // Do a resequence tidy-up, in case the instance list passed in was not complete.
        // Limit the reorder to just this group to avoid updating more than is necessary.
        xarMod::apiFunc('blocks', 'admin', 'resequence', array('id' => $id));
    }

    return true;
}

?>
