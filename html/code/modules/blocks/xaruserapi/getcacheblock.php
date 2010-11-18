<?php
/**
 * Retrieve a cache block instance
 *
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
*/
/**
 * @param array    $args array of optional parameters<br/>
*/
function blocks_userapi_getcacheblock(Array $args=array())
{
    extract($args);

    // Argument check
    if(!isset($bid)) throw new EmptyParameterException('bid');
    if(!is_numeric($bid)) throw new BadParameterException($bid);

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $cacheBlockTable = $xartable['cache_blocks'];
    $instance = array();

    $query = "SELECT blockinstance_id, nocache, page, theuser, expire
              FROM $cacheBlockTable
              WHERE blockinstance_id = ?";
    $result = $dbconn->Execute($query,array($bid));
    if($result->next()) {
        // and if there is one (assuming only one here but there is a constraint on the table) grab it
        list($bid, $nocache, $page, $user, $expire) = $result->fields;
        $instance = array('id'    => $bid, 'nocache' => $nocache,
                          'page'   => $page,'user'    => $user,
                          'expire' => $expire);
    }
    $result->close();

    /* Return the instance array */
    return $instance;
}
?>