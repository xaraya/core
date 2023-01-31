<?php
/**
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_241_03()
{
    // Define parameters
    $prefix = xarDB::getPrefix();
    $blocktypes_table = $prefix . '_block_types';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Add class and filepath columns to the block_types table
    ");
    $data['reply'] = xarML("
        Success!
    ");

    //Load Table Maintainance API
    sys::import('xaraya.tableddl');
    // alter block_types table
    $dbconn  = xarDB::getConn();
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
        /**
         * ALTER TABLE xar_block_types ADD COLUMN class VARCHAR(254) NOT NULL DEFAULT '';
         */
        $args = ['command' => 'add', 'field' => 'class', 'type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset, 'default' => ''];

        // Alter the block_types table
        $query = xarTableDDL::alterTable($blocktypes_table, $args);
        $dbconn->Execute($query);

        /**
         * ALTER TABLE xar_block_types ADD COLUMN filepath VARCHAR(254) NOT NULL DEFAULT '';
         */
        $args = ['command' => 'add', 'field' => 'filepath', 'type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset, 'default' => ''];

        // Alter the block_types table
        $query = xarTableDDL::alterTable($blocktypes_table, $args);
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
