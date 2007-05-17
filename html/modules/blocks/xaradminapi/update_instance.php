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
 * update attributes of a block instance
 *
 * @author Jim McDonald, Paul Rosania
 * @param $args['bid'] the ID of the block to update
 * @param $args['title'] the new title of the block
 * @param $args['group_id'] the new position of the block (deprecated)
 * @param $args['groups'] optional array of group memberships
 * @param $args['template'] the template of the block instance
 * @param $args['content'] the new content of the block
 * @param $args['refresh'] the new refresh rate of the block
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_update_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Optional arguments
    if (!isset($content)) {
        $content = '';
    }

    // The content no longer needs to be serialized before it gets here.
    // Lets keep the serialization close to where it is stored (since
    // storage is the only reason we do it).
    if (!is_string($content)) {
        $content = serialize($content);
    }

    if (!isset($template)) {
        $template = '';
    }

    // Argument check
    if (!xarVarValidate('pre:lower:ftoken:passthru:str:1', $name) ||
        (!isset($bid) || !is_numeric($bid)) ||
        (!isset($title)) ||
        (!isset($refresh) || !is_numeric($refresh)) ||
        (!isset($state)  || !is_numeric($state))) {
        throw new BadParameterException(null,'Invalid number of parameters or wrong values in blocks_adminapi_update_instance');
    }

    // Legacy support of group_id
    if (!isset($groups) && isset($group_id)) {
        $groups = array(
            array('gid' => $group_id, 'template' => '')
        );
    }

    // TODO: check for unique name before updating the database (errors raised
    // by unique keys are not user-friendly).
    $name = strtolower($name);

    // Security
    // TODO: add security on the name as well as (eventually instead of) the title.
    if(!xarSecurityCheck('EditBlock', 1, 'Block', "$title::$bid")) {return;}

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    try {
        $dbconn->begin();
        $query = 'UPDATE ' . $block_instances_table . '
                  SET content=?, template=?, name=?, title=?, refresh=?, state=?
                  WHERE id = ?';
        $stmt = $dbconn->prepareStatement($query);
        $bind = array($content, $template, $name, $title,$refresh, $state, $bid);
        $stmt->executeUpdate($bind);

        // Update the group instances.
        if (isset($groups) && is_array($groups)) {
            // Pass the group updated to the API if required.
            // TODO: error handling.
            $result = xarModAPIfunc('blocks', 'admin', 'update_instance_groups',array('bid' => $bid, 'groups' => $groups));
        }

        $args['module'] = 'blocks';
        $args['itemtype'] = 3; // block instance
        $args['itemid'] = $bid;
        xarModCallHooks('item', 'update', $bid, $args);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    return true;
}

?>
