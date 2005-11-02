<?php
/**
 * Delete a group & info
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/* deletegroup - delete a group & info
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
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
