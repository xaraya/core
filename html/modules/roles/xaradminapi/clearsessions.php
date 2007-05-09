<?php
/**
 * Delete a group & info
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
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

    $query = "SELECT sessid, role_id FROM $sessionstable";
    $result = $dbconn->executeQuery($query);

    // Prepare query outside the loop
    $sql = "DELETE FROM $sessionstable WHERE sessid = ?";
    $stmt = $dbconn->prepareStatement($sql);
    try {
        $dbconn->begin();
        while ($result->next()) {
            list($thissession, $thisuid) = $result->fields;
            foreach ($spared as $uid) {
                $thisrole = xarRoles::get($thisuid);
                $thatrole = xarRoles::get($uid);
                if (!$thisuid == $uid && !$thisrole->isParent($thatrole)) {
                    $stmt->executeUpdate(array($thisuid));
                    break;
                }
            }
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
