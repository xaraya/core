<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */

function sql_210_roles_tree()
{
    // Define parameters
    $roles = xarDB::getPrefix() . '_roles';
    $rolemembers = xarDB::getPrefix() . '_rolemembers';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Checking for consistency in the roles tree
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        SELECT 
        `id`,
        `uname`
        FROM $roles r LEFT JOIN $rolemembers rm ON r.id = rm.role_id WHERE r.id != 1 AND rm.parent_id IS NULL
        ";
        $result = $dbconn->Execute($data['sql']);
        if (!$result->EOF) {
            list($id, $uname) = $result->fields;
            $data['success'] = false;
            $data['reply'] = xarML("
            No parent: $uname
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