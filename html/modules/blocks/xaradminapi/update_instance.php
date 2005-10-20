<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
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
        $msg = xarML('Invalid parameter');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
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

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = 'UPDATE ' . $block_instances_table . '
              SET xar_content = ?,
                  xar_template = ?,
                  xar_name = ?,
                  xar_title = ?,
                  xar_refresh = ?,
                  xar_state = ?
              WHERE xar_id = ?';

    $bind = array(
        $content, $template, $name, $title,
        $refresh, $state, $bid
    );

    $result = $dbconn->Execute($query, $bind);
    if (!$result) {return;}

    // Update the group instances.
    if (isset($groups) && is_array($groups)) {
        // Pass the group updated to the API if required.
        // TODO: error handling.
        $result = xarModAPIfunc(
            'blocks', 'admin', 'update_instance_groups',
            array('bid' => $bid, 'groups' => $groups)
        );
    }
    
    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarModCallHooks('item', 'update', $bid, $args);
    
    return true;
}

?>
