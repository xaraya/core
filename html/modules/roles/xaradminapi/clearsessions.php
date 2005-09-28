<?php
/**
 * File: $Id$
 *
 * Delete a group and info
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * deletegroup - delete a group & info
 * @param $args['uid']
 * @return true on success, false otherwise
 */
function roles_adminapi_clearsessions($spared)
{
    if(!isset($spared)) {
        $msg = xarML('Wrong arguments to groups_adminapi_clearsessions');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $sessionstable = $xartable['session_info'];
    $roles = new xarRoles();

    $query = "SELECT xar_sessid, xar_uid FROM $sessionstable";
    $result = $dbconn->Execute($query);
    if (!$result) return;
    while (!$result->EOF) {
       list($thissession, $thisuid) = $result->fields;
       foreach ($spared as $uid) {
            $thisrole = $roles->getRole($thisuid);
            $thatrole = $roles->getRole($uid);
            if (!$thisuid == $uid && !$thisrole->isParent($thatrole)) {
                $query = "DELETE FROM $sessionstable
                  WHERE xar_sessid = ?";
                if (!$dbconn->Execute($query,array($thisuid))) return;
                break;
            }
        }
       $result->MoveNext();
   }

// Security Check
    if(!xarSecurityCheck('EditRole')) return;


    return true;
}

?>