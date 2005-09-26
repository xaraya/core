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
 * update reg'd users foldable menus
 * @author Jim McDonald, Paul Rosania
 * @param $args['bid'] blockid to fold
 * @return true on success, false on failure
 */
/*
function blocks_userapi_update($args)
{
    extract($args);

    if(!isset($bid)) {
        xarSessionSetVar('errmsg', 'Error in API');
        return false;
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $uid = xarUserGetVar('uid');

    if (empty($uid)) {return;}

    $ublockstable =& $xartable['userblocks'];

    $query = 'SELECT xar_active FROM ' . $ublockstable 
        . ' WHERE xar_bid = ? AND xar_uid = ?';

    $result =& $dbconn->Execute($query, array($bid, $uid));
    if (!$result) {
        return;
    }

    list($active) = $result->fields;
    if ($active) {
        $active = 0;
    } else {
        $active = 1;
    }
    $query = 'UPDATE ' . $ublockstable 
        . ' SET xar_active = ?'
        . ' WHERE xar_uid = ? AND xar_bid = ?';

    $result = $dbconn->Execute($query, array($active, $uid, $bid));

    return;
}
*/
?>
