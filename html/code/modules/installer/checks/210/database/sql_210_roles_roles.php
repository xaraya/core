<?php
/**
 * Check SQL file
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_roles_roles()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_roles';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Checking for required roles objects
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        SELECT 
        `id`,
        `uname`
        FROM $table WHERE uname ='everybody'
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing Everybody group
            ");
        }

        $data['sql'] = "
        SELECT 
        `id`,
        `uname`
        FROM $table WHERE uname ='administrators'
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing Administrators group
            ");
        }
        
        $data['sql'] = "
        SELECT 
        `id`,
        `uname`
        FROM $table WHERE uname ='sitemanagers'
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing SiteManagers group
            ");
        }

        $data['sql'] = "
        SELECT 
        `id`,
        `uname`
        FROM $table WHERE uname ='users'
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing Users group
            ");
        }

        $data['sql'] = "
        SELECT 
        `id`,
        `uname`
        FROM $table WHERE id = " . xarConfigVars::get(null, 'Site.User.AnonymousUID') . "
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing Anonymous user
            ");
        }

        $data['sql'] = "
        SELECT 
        `id`,
        `uname`
        FROM $table WHERE id = " . xarModVars::get('roles','admin') . "
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing designater admin user
            ");
        }
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