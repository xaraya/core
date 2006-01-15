<?php
/**
 * create a new block instance
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * create a new block instance
 * @author Jim McDonald, Paul Rosania
 * @param $args['name'] unique name for the block
 * @param $args['title'] the title of the block
 * @param $args['type'] the block's type
 * @param $args['template'] the block's template
 * @returns int
 * @return block instance id on success, false on failure
 */
function blocks_adminapi_create_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if ((!isset($name) || !xarVarValidate('pre:lower:ftoken:passthru:str:1', $name)) ||
        (!isset($type) || !is_numeric($type)) ||
        (!isset($state) || !is_numeric($state))) {
        // TODO: this type of error to be handled automatically
        // (i.e. no need to pass the position through the error message, as the
        // error handler should already know).
        $msg = xarML('Invalid Parameter Count', 'admin', 'create', 'Blocks');
        xarErrorSet(
            XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
            new SystemException($msg)
        );
        return;
    }

    // Security.
    // TODO: fix this. A security check on whether a certain title can be created
    // does not make any sense to me. Probably remove it.
    //if (!xarSecurityCheck('AddBlock', 1, 'Block', "All:$title:All")) {return;}

    // Make sure type exists.
    $blocktype = xarModAPIfunc('blocks', 'user', 'getblocktype', array('tid' => $type));

    $initresult = xarModAPIfunc('blocks', 'user', 'read_type_init', $blocktype);

    // If the content is not set, attempt to get initial content from
    // the block initialization function.
    if (!isset($content)) {
        $content = '';

        if (!empty($initresult)) {
            $content = $initresult;
        }
    }

    if (!empty($content) && !is_string($content)) {
        // Serialize the content, so arrays of initial content
        // can be passed directly into this API.
        $content = serialize($content);
    }

    if (!isset($template)) $template = '';
    if (!isset($title)) $title = '';

    // Load up database details.
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_instances_table = $xartable['block_instances'];

    // Insert instance details.
    $nextId = $dbconn->GenId($block_instances_table);
    $query = 'INSERT INTO ' . $block_instances_table . ' (
              xar_id,
              xar_type_id,
              xar_name,
              xar_title,
              xar_content,
              xar_template,
              xar_state,
              xar_refresh,
              xar_last_update
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $result =& $dbconn->Execute(
        $query, array(
                      $nextId, $type, $name, $title, $content, $template, $state,0,0
        )
    );
    if (!$result) {return;}

    // Get ID of row inserted.
    $bid = $dbconn->PO_Insert_ID($block_instances_table, 'xar_id');

    // Update the group instances.
    if (isset($groups) && is_array($groups)) {
        // Pass the group updated to the API if required.
        // TODO: error handling.
        $result = xarModAPIfunc(
            'blocks', 'admin', 'update_instance_groups',
            array('bid' => $bid, 'groups' => $groups)
        );
    }

    // Insert defaults for block caching (based on block init array)
    if (!empty($initresult) && is_array($initresult) && !empty($xartable['cache_blocks'])) {
        if (!empty($initresult['nocache'])) {
            $nocache = 1;
        } else {
            $nocache = 0;
        }
        if (!empty($initresult['pageshared']) && is_numeric($initresult['pageshared'])) {
            $pageshared = (int) $initresult['pageshared'];
        } else {
            $pageshared = 0;
        }
        if (!empty($initresult['usershared']) && is_numeric($initresult['usershared'])) {
            $usershared = (int) $initresult['usershared'];
        } else {
            $usershared = 0;
        }
        // don't use empty because this could be 0 here
        if (isset($initresult['cacheexpire']) && is_numeric($initresult['cacheexpire'])) {
            $cacheexpire = (int) $initresult['cacheexpire'];
        } else {
            $cacheexpire = NULL;
        }
        $cacheblocks = $xartable['cache_blocks'];
        $query = "INSERT INTO $cacheblocks (xar_bid,
                                            xar_nocache,
                                            xar_page,
                                            xar_user,
                                            xar_expire)
                  VALUES (?,?,?,?,?)";
        $bindvars = array($bid, $nocache, $pageshared, $usershared, $cacheexpire);
        $result =& $dbconn->Execute($query,$bindvars);
        if (!$result) {return;}
    }

    // Resequence the blocks.
    xarModAPIFunc('blocks', 'admin', 'resequence');

    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarModCallHooks('item', 'create', $bid, $args);

    return $bid;
}

?>
