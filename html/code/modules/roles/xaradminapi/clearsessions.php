<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Clear sessions 
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['id']
 * @return boolean true on succes, false on failure
 * @todo Move this to sessions subsystem, doesnt belong here.
 */
function roles_adminapi_clearsessions($spared)
{
    if(!isset($spared)) throw new EmptyParameterException('spared');

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $sessionstable = $xartable['session_info'];

    $query = "SELECT id, role_id FROM $sessionstable";
    $result = $dbconn->executeQuery($query);

    // Prepare query outside the loop
    $sql = "DELETE FROM $sessionstable WHERE id = ?";
    $stmt = $dbconn->prepareStatement($sql);
    try {
        $dbconn->begin();
        while ($result->next()) {
            list($thissession, $thisid) = $result->fields;
            foreach ($spared as $id) {
                $thisrole = xarRoles::get($thisid);
                $thatrole = xarRoles::get($id);
                if (!$thisid == $id && !$thisrole->isParent($thatrole)) {
                    $stmt->executeUpdate(array($thisid));
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
    if(!xarSecurityCheck('EditRoles')) return;


    return true;
}

?>