<?php
/** 
 * File: $Id$
 *
 * Get block state
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
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

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $ublockstable = $xartable['userblocks'];

    if (!isset($userblocks[$uid])) {
        $userblocks[$uid] = array();

        $query = "SELECT xar_bid, xar_active FROM $ublockstable WHERE xar_uid = $uid";

        $result =& $dbconn->Execute($query);
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

    $uid = xarVarPrepForStore($uid);
    $bid = xarVarPrepForStore($bid);

    // Check to see if record exists before inserting it
    $query = "SELECT xar_uid FROM $ublockstable WHERE xar_uid = $uid AND xar_bid='$bid'";
    $result =& $dbconn->Execute($query);
    if ($result->EOF) {
        $query="INSERT INTO $ublockstable
                        (xar_uid,
                        xar_bid,
                        xar_active)
                        VALUES
                        (".xarVarPrepForStore($uid).",
                        '$bid',
                        '1')";
        $result =& $dbconn->Execute($query);
        if (!$result) return;
    }

    $userblocks[$uid][$bid] = 1;
    return true;
}
*/
?>