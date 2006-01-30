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
 * @todo Move this to sessions subssystem, doesnt belong here.
 */
function roles_adminapi_clearsessions($spared)
{
    if(!isset($spared)) throw new EmptyParameterException('spared');

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $sessionstable = $xartable['session_info'];
    $roles = new xarRoles();

    $query = "SELECT xar_sessid, xar_uid FROM $sessionstable";
    $result = $dbconn->Execute($query);

    // Prepare query outside the loop
    $sql = "DELETE FROM $sessionstable WHERE xar_sessid = ?";
    $stmt = $dbconn->prepareStatement($sql);
    try {
        $dbconn->begin();
        while (!$result->EOF) {
            list($thissession, $thisuid) = $result->fields;
            foreach ($spared as $uid) {
                $thisrole = $roles->getRole($thisuid);
                $thatrole = $roles->getRole($uid);
                if (!$thisuid == $uid && !$thisrole->isParent($thatrole)) {
                    $stmt->executeUpdate(array($thisuid));
                    break;
                }
            }
            $result->MoveNext();
        }
        $dbconn->commit();
    } catch(SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

// Security Check
    if(!xarSecurityCheck('EditRole')) return;


    return true;
}

?>
