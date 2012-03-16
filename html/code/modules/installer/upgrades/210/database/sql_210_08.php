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
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_08()
{
    // Define parameters
    $roles = xarDB::getPrefix() . '_roles';
    $rolemembers = xarDB::getPrefix() . '_rolemembers';
    $privileges = xarDB::getPrefix() . '_privileges';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding the 'Site Management' privilege, SiteManagers group and SiteManager user
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype) VALUES ('SiteManagement', 0, 'All', 'All', 700, 'Site Manager access to all modules', 2)
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        INSERT INTO $roles (name, itemtype,  users, uname, date_reg, valcode, state, auth_module_id) VALUES ('SiteManagers', 3, 1, 'sitemanagers', UNIX_TIMESTAMP(), 'createdbysystem', 3, 4)
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        INSERT INTO $roles (name, itemtype,  users, uname, email, date_reg, valcode, state, auth_module_id) VALUES ('SiteManager', 2, 0, 'manager', 'none@none.com', UNIX_TIMESTAMP(), 'createdbysystem', 3, 4)
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        SELECT id FROM $roles WHERE name = 'sitemanagers'
        ";
        $result = $dbconn->Execute($data['sql']);
        list($idgroup) = $result->fields;
        $data['sql'] = "
        INSERT INTO $rolemembers (role_id, parent_id) VALUES ($idgroup,1)
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        SELECT id FROM $roles WHERE uname = 'manager'
        ";
        $result = $dbconn->Execute($data['sql']);
        list($iduser) = $result->fields;
        $data['sql'] = "
        INSERT INTO $rolemembers (role_id, parent_id) VALUES ($iduser,$idgroup)
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