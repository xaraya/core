<?php
/**
 * Upgrade SQL file
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

function sql_210_22()
{
    // Define parameters
    $table['block_types'] = xarDB::getPrefix() . '_block_types';
    $table['block_instances'] = xarDB::getPrefix() . '_block_instances';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Changing the blocks of type 'html', 'php' and 'text', 'finclude' to 'content'
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $table[block_instances] SET type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'content') WHERE type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'html');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table[block_instances] SET type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'content') WHERE type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'php');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table[block_instances] SET type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'content') WHERE type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'text');
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