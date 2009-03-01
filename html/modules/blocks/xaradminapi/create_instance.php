<?php
/**
 * create a new block instance
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
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
        throw new BadParameterException(null,'Wrong number of arguments or wrong arguments in functions blocks_adminapi_create_instance');
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
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $block_instances_table = $xartable['block_instances'];

    // Insert instance details.
    $query = 'INSERT INTO ' . $block_instances_table . ' (
              type_id, name,
              title, content, template,
              state, refresh, last_update
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

    $dbconn->Execute($query, array($type, $name, $title, $content, $template, $state,0,0));

    // Get ID of row inserted.
    $bid = $dbconn->getLastId($block_instances_table);

    // Update the group instances.
    if (isset($groups) && is_array($groups)) {
        // Pass the group updated to the API if required.
        // TODO: error handling.
        $result = xarModAPIfunc('blocks', 'admin', 'update_instance_groups',
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
        //check and see if there is an entry already before trying to add one - bug # 5815
        $checkbid = xarModAPIFunc('blocks','user','getcacheblock',array('bid'=>$bid));
        //we assume for now that it's left here due to bug # 5815 so delete it
        if (is_array($checkbid)) {
           $deletecacheblock = xarModAPIFunc('blocks','admin','delete_cacheinstance', array('bid' => $bid));
        }
        //now create the new block
        $cacheblocks = $xartable['cache_blocks'];
        $query = "INSERT INTO $cacheblocks (blockinstance_id,
                                            nocache,
                                            page,
                                            user,
                                            expire)
                  VALUES (?,?,?,?,?)";
        $bindvars = array($bid, $nocache, $pageshared, $usershared, $cacheexpire);
        $dbconn->Execute($query,$bindvars);
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
