<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_07()
{
    // Define parameters
    $dynamic_objects = xarDB::getPrefix() . '_dynamic_objects';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding configuration information to objects 'objects' and 'properties'
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $dynamic_objects SET `config` = 'a:3:{s:14:\"display_access\";a:3:{s:5:\"group\";s:1:\"0\";s:5:\"level\";s:3:\"200\";s:7:\"failure\";s:1:\"0\";}s:13:\"modify_access\";a:3:{s:5:\"group\";s:1:\"0\";s:5:\"level\";s:3:\"800\";s:7:\"failure\";s:1:\"0\";}s:13:\"delete_access\";a:3:{s:5:\"group\";s:1:\"0\";s:5:\"level\";s:3:\"800\";s:7:\"failure\";s:1:\"0\";}}'
        WHERE id = 1;
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $dynamic_objects SET `config` = 'a:3:{s:14:\"display_access\";a:3:{s:5:\"group\";s:1:\"0\";s:5:\"level\";s:3:\"200\";s:7:\"failure\";s:1:\"0\";}s:13:\"modify_access\";a:3:{s:5:\"group\";s:1:\"0\";s:5:\"level\";s:3:\"800\";s:7:\"failure\";s:1:\"0\";}s:13:\"delete_access\";a:3:{s:5:\"group\";s:1:\"0\";s:5:\"level\";s:3:\"800\";s:7:\"failure\";s:1:\"0\";}}'
        WHERE id = 2;
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