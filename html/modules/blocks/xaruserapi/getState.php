<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/*
 * Get block state
 * @author Jim McDonald, Paul Rosania
*/
/*
function blocks_userapi_getState($args)
{
    static $userblocks = array();

    if (!xarUserIsLoggedIn()) {
        return true;
    }

    extract($args);

    $uid = xarUserGetVar('uid');
    if (empty($uid)){
        $uid = 2;
    }

    if (isset($userblocks[$uid][$bid])) {
        return $userblocks[$uid][$bid];
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $ublockstable = $xartable['userblocks'];

    if (!isset($userblocks[$uid])) {
        $userblocks[$uid] = array();

        $query = "SELECT xar_bid, xar_active FROM $ublockstable WHERE xar_uid = ?";

        $result =& $dbconn->Execute($query,array($uid));
        if (!$result) return;

        while (!$result->EOF) {
            list($block,$active) = $result->fields;
            $userblocks[$uid][$block] = $active;
            $result->MoveNext();
        }
        $result->Close();

        if (isset($userblocks[$uid][$bid])) {
            return $userblocks[$uid][$bid];
        }
    }

    // Check to see if record exists before inserting it
    $query = "SELECT xar_uid FROM $ublockstable WHERE xar_uid = ? AND xar_bid=?";
    $result =& $dbconn->Execute($query,array($uid,$bid));
    if ($result->EOF) {
        $query="INSERT INTO $ublockstable (xar_uid, xar_bid, xar_active)
                VALUES (?,?,?)";
        $result =& $dbconn->Execute($query,array($uid,$bid,1));
        if (!$result) return;
    }

    $userblocks[$uid][$bid] = 1;
    return true;
}
*/
?>