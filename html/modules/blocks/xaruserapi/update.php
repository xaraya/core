<?php
/** 
 * File: $Id$
 *
 * Update registered users' foldable menus
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * update reg'd users foldable menus
 * @param $args['bid'] blockid to fold
 * @return true on success, false on failure
 */
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

    $ublockstable = $xartable['userblocks'];

    $query="SELECT xar_active 
              FROM $ublockstable 
             WHERE xar_bid='".xarVarPrepForStore($bid)."' AND xar_uid=".xarVarPrepForStore($uid)."";

    $result =& $dbconn->Execute($query);
    if (!$result) 
        return;

    list($active) = $result->fields;
    if ($active) {
        $active = 0;
    } else {
        $active = 1;
    }
    $query="UPDATE $ublockstable 
               SET xar_active='".xarVarPrepForStore($active)."' 
             WHERE xar_uid=".xarVarPrepForStore($uid)." AND xar_bid='".xarVarPrepForStore($bid)."'";

    $result =& $dbconn->Execute($query);
    if (!$result) 
        return;

    return;
}
?>