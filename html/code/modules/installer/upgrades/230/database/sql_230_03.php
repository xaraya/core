<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_230_03()
{
    // Define parameters
    $prefix = xarDB::getPrefix();
    $table = $prefix . '_themes_configurations';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Creating themes configurations table
    ");
    $data['reply'] = xarML("
        Success!
    ");    
    
    //Load Table Maintainance API
    sys::import('xaraya.tableddl');    
    // create table
    $dbconn  = xarDB::getConn();
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
        /**
        * CREATE TABLE `xar_themes_configurations` (
        *   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        *   `theme_id` int(10) unsigned NOT NULL DEFAULT '0',
        *   `name` varchar(64) NOT NULL DEFAULT '',
        *   `description` varchar(254) NOT NULL DEFAULT '',
        *   `property_id` int(10) unsigned NOT NULL DEFAULT '0',
        *   `label` varchar(254) NOT NULL DEFAULT '',
        *   `configuration` mediumtext NOT NULL,
        *   PRIMARY KEY (`id`)
         */
        $fields = array(
            'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true,     'primary_key' => true),
            'theme_id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),    
            'name' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'default' => '', 'charset' => $charset),
            'description' => array('type' => 'varchar', 'size' => 254, 'null' => false, 'default' => '', 'charset' => $charset),
            'property_id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),
            'label' => array('type' => 'varchar', 'size' => 254, 'null' => false, 'default' => '', 'charset' => $charset),
            'configuration' => array('type' => 'text', 'null' => false, 'charset' => $charset)
        );

        // Create the eventsystem table
        $query = xarDBCreateTable($table, $fields);
        $dbconn->Execute($query);
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