<?php
/**
 * Create a cache block instance
 *
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
 * create a cache block
 *
 * @param $args['bid'] the ID of the block to create
 * @return bool true on success, false on failure
 */
function blocks_adminapi_create_cacheinstance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if(!isset($bid)) throw new EmptyParameterException('bid');
    if(!is_numeric($bid)) throw new BadParameterException($bid);

    // Security
    if (!xarSecurityCheck('AddBlocks', 1, 'Block', "::$bid")) {return;}

    if (!empty($nocache)) {
        $nocache = true;
    } else {
        $nocache = false;
    }
    if (!empty($pageshared) && is_numeric($pageshared)) {
        $pageshared = (bool) $pageshared;
    } else {
        $pageshared = false;
    }
    if (!empty($usershared) && is_numeric($usershared)) {
        $usershared = (int) $usershared;
    } else {
        $usershared = 0;
    }
    // don't use empty because this could be 0 here
    if (isset($cacheexpire) && is_numeric($cacheexpire)) {
        $cacheexpire = (int) $cacheexpire;
    } else {
        $cacheexpire = NULL;
    }

    // Load up database details.
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    //check and see if there is an entry already before trying to add one - bug # 5815
    $checkbid = xarMod::apiFunc('blocks','user','getcacheblock',array('bid'=>$bid));
    //we assume for now that it's left here due to bug # 5815 so delete it
    if (is_array($checkbid)) {
       $deletecacheblock = xarMod::apiFunc('blocks','admin','delete_cacheinstance', array('bid' => $bid));
    }

    //now create the new block
    $cacheblocks = $xartable['cache_blocks'];
    $query = "INSERT INTO $cacheblocks (blockinstance_id,
                                        nocache,
                                        page,
                                        theuser,
                                        expire)
              VALUES (?,?,?,?,?)";
    $bindvars = array($bid, $nocache, $pageshared, $usershared, $cacheexpire);
    $dbconn->Execute($query,$bindvars);

    return true;
}
?>