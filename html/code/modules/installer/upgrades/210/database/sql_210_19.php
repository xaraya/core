<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_210_19()
{
    // Define parameters
    $dynamic_objects = xarDB::getPrefix() . '_dynamic_objects';
    $roles = xarDB::getPrefix() . '_roles';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Remove the roles_roles object and reset the user and group itemtypes
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        DELETE FROM $dynamic_objects WHERE name = 'roles_roles';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $dynamic_objects SET itemtype = 1 WHERE module_id = 27 AND itemtype = 2;
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $dynamic_objects SET itemtype = 2 WHERE module_id = 27 AND itemtype = 3;
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $roles SET itemtype = 1 WHERE itemtype = 2;
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $roles SET itemtype = 2 WHERE itemtype = 3;
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