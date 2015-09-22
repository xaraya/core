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

function sql_210_06()
{
    // Define parameters
    $privileges = xarDB::getPrefix() . '_privileges';
    $privmembers = xarDB::getPrefix() . '_privmembers';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Removing the 'DenyBlocks' privilege
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        DELETE p, pm FROM $privileges p INNER JOIN $privmembers pm WHERE p.id = pm.privilege_id AND p.name = 'DenyBlocks' AND p.itemtype= 2;
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