<?php
/**
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
 * activate a block
 * @author Jim McDonald
 * @author Paul Rosania
 * @param array    $args array of optional parameters<br/>
 * @param $args['bid'] the ID of the block to activate
 * @return boolean true on success, false on failure
 */
function blocks_adminapi_activate(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) throw new BadParameterException('bid');

    // Security
    if(!xarSecurityCheck('ActivateBlocks',1,'Block',"::$bid")) {return;}

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $blockstable = $xartable['block_instances'];

    // Activate
    $query = "UPDATE $blockstable SET state=? WHERE id=?";
    $dbconn->Execute($query,array(2,$bid));
    return true;
}

?>