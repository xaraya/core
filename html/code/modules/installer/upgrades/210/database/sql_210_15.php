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

function sql_210_15()
{
    // Define parameters
    $privileges = xarDB::getPrefix() . '_privileges';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Redefining mask names in the Roles module
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $privileges SET name = 'ReadRoles' WHERE name = 'ReadRole';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'EditRoles' WHERE name = 'EditRole';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'AddRoles' WHERE name = 'AddRole';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'AdminRoles' WHERE name = 'AdminRole';
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
