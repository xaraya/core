<?php

/**
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_220_03()
{
    // Define parameters
    $prefix = xarDB::getPrefix();
    $events_table = $prefix . '_eventsystem';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Creating event system table
    ");
    $data['reply'] = xarML("
        Success!
    ");

    //Load Table Maintainance API
    sys::import('xaraya.tableddl');
    // create eventsystem table
    $dbconn  = xarDB::getConn();
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
        /**
         * CREATE TABLE xar_eventsystem (
         *   id integer NOT NULL auto_increment,
         *   event      varchar(254) NOT NULL,
         *   module_id  integer default 0,
         *   itemtype   integer default 0
         *   area       varchar(64) NOT NULL,
         *   type       varchar(64) NOT NULL,
         *   func       varchar(64) NOT NULL,
         *   scope      varchar(64) NOT NULL,
         *   PRIMARY KEY (id)
         * )
         */
        $fields = [
            'id' => ['type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true,     'primary_key' => true],
            'event' => ['type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset],
            'module_id' => ['type' => 'integer', 'size' => 11, 'unsigned' => true, 'null' => false, 'default' => '0'],
            'itemtype' => ['type' => 'integer', 'size' => 11, 'unsigned' => true, 'null' => false, 'default' => '0'],
            'area' => ['type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset],
            'type' => ['type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset],
            'func' => ['type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset],
            'scope' => ['type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset],
        ];

        // Create the eventsystem table
        $query = xarTableDDL::createTable($events_table, $fields);
        $dbconn->Execute($query);

        // each entry should be unique
        $index = ['name'   => 'i_' . $prefix . '_eventsystem',
            'fields' => ['event', 'module_id', 'itemtype'],
            'unique' => true];

        $query = xarTableDDL::createIndex($events_table, $index);
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
