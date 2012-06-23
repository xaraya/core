<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_02()
{
    // Define parameters
    $dynamic_configurations = xarDB::getPrefix() . '_dynamic_properties_def';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Changing the dynamic_properties_def.modid default value from null to 0
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
            ALTER TABLE `xar_dynamic_properties_def` CHANGE `modid` `modid` integer unsigned NOT NULL default '0'
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
