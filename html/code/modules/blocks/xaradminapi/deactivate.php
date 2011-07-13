<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * deactivate a block
 * @author Jim McDonald
 * @author Paul Rosania
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['bid'] the ID of the block to deactivate
 * @return boolean true on success, false on failure
 */
function blocks_adminapi_deactivate(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid)) {
        xarSession::setVar('errormsg', _MODARGSERROR);
        return false;
    }

    // Security
    if(!xarSecurityCheck('ActivateBlocks',1,'Block',"::$bid")) return;

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $blockstable = $xartable['block_instances'];

    // Deactivate
    $query = "UPDATE $blockstable SET state = ?  WHERE id = ?";
    $dbconn->Execute($query,array(0, $bid));

    return true;
}

?>