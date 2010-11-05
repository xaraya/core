<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_01()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_modules';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Upgrading the core module version numbers
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'authsystem';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'base';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'blocks';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'dynamicdata';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'installer';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'mail';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'modules';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'privileges';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'roles';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table SET version = '2.1.0' WHERE `name` = 'themes';
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