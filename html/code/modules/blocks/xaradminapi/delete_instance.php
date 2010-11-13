<?php
/**
 * Delete a block instance
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
 * delete a block
 * @author Jim McDonald
 * @author Paul Rosania
 * @param array    $args array of optional parameters<br/>
 * @param $args['bid'] the ID of the block to delete
 * @return boolean true on success, false on failure
 */
function blocks_adminapi_delete_instance(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) throw new BadParameterException('bid');

    // Security
    if (!xarSecurityCheck('ManageBlocks', 1, 'Block', "::$bid")) {return;}

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "DELETE FROM $block_group_instances_table
              WHERE instance_id = ?";
    $result = $dbconn->Execute($query,array($bid));

    $query = "DELETE FROM $block_instances_table
              WHERE id = ?";
    $result = $dbconn->Execute($query,array($bid));

    //let's make sure the cache blocks instance as well is deleted, if it exists bug #5815
    if (!empty($xartable['cache_blocks'])) {
        $deletecacheblock = xarMod::apiFunc('blocks','admin','delete_cacheinstance', array('bid' => $bid));
    }

    xarMod::apiFunc('blocks', 'admin', 'resequence');

    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarModCallHooks('item', 'delete', $bid, $args);

    return true;
}

?>