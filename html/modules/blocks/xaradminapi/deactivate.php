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
 * deactivate a block
 * @author Jim McDonald, Paul Rosania
 * @param $args['bid'] the ID of the block to deactivate
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_deactivate($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid)) {
        xarSession::setVar('errormsg', _MODARGSERROR);
        return false;
    }

    // Security
    if(!xarSecurityCheck('CommentBlock',1,'Block',"::$bid")) return;

    $dbconn = xarDB::getConn();
    $xartable =& xarDBGetTables();
    $blockstable = $xartable['block_instances'];

    // Deactivate
    $query = "UPDATE $blockstable SET state = ?  WHERE id = ?";
    $dbconn->Execute($query,array(0, $bid));

    return true;
}

?>
