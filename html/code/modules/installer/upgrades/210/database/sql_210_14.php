<?php
/**
 * Upgrade SQL file
 *
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */
function sql_210_14()
{
    // Define parameters
    $privileges = xarDB::getPrefix() . '_privileges';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Removing old masks from the Privileges module
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        DELETE FROM $privileges WHERE $privileges.`name` = 'DeletePrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        DELETE FROM $privileges WHERE $privileges.`name` = 'ViewPrivileges';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        DELETE FROM $privileges WHERE $privileges.`name` = 'AssignPrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        DELETE FROM $privileges WHERE $privileges.`name` = 'DeassignPrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $dbconn->commit();
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;
}
