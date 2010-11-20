<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage installer module
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_13()
{
    // Define parameters
    $privileges = xarDB::getPrefix() . '_privileges';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Redefining mask names in the Privileges module
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $privileges SET name = 'EditPrivileges' WHERE name = 'EditPrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'AddPrivileges' WHERE name = 'AddPrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'AdminPrivileges' WHERE name = 'AdminPrivilege';
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
?>