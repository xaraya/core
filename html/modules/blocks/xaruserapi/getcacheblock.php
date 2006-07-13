<?php
/** 
 * Retrieve a cache block instance
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
*/
function blocks_userapi_getcacheblock($args)
{
    extract($args);

    if (!isset($bid) || !is_numeric($bid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            'item ID', 'user', 'getcacheblock', 'Blocks');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
            new SystemException($msg));
        return;
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $cacheBlockTable = $xartable['cache_blocks'];

    $query = "SELECT xar_bid, xar_nocache, xar_page, xar_user, xar_expire
              FROM $cacheBlockTable
              WHERE xar_bid = ?";
    
    $result = &$dbconn->Execute($query,array($bid));
    if (!$result) return;
    // make sure there is a result
    if ($result->EOF) {
        $result->Close();
        return;
    }
    //and if there is one (assuming only one here but there is a constraint on the table) grab it
    list($bid, $nocache, $page, $user, $expire) = $result->fields;
    
    //close the connection neow
    $result->Close();
    $instance = array('bid'     => $bid,
                      'nocache' => $nocache,
                      'page'    => $page,
                      'user'    => $user,
                      'expire'  => $expire);

    /* Return the instance array */
    return $instance;
}
?>