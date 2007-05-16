<?php
/**
 * Creole wrapper class
 *
 * @package lib
 * @subpackage database
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @link http://www.xaraya.com
 * @author Marcel van der Boom <marcel@hsdev.com>
 */
sys::import('creole.Creole');
class xarDB extends Creole
{
    public static $count = 0;

    // Instead of the globals, we save our db info here.
    private static $firstDSN = null;
    private static $connections = array();
    private static $tables = array();
    private static $prefix = '';

    public static function getPrefix()
    {
        return self::$prefix;
    }

    public static function setPrefix($prefix)
    {
        self::$prefix =  $prefix;
    }

    /**
     * Get an array of database tables
     *
     * @return array array of database tables
     * @todo we should figure something out so we dont have to do the getTables stuff, it should be transparent
     */
    public static function &getTables() {  return self::$tables; }

    public static function importTables(Array $tables = array())
    {
        self::$tables = array_merge(self::$tables,$tables);
    }

    public static function getHost()
    {
        if(!isset(self::$firstDSN)) self::setFirstDSN();
        return self::$firstDSN['hostspec'];
    }

    public static function getType()
    {
        if(!isset(self::$firstDSN)) self::setFirstDSN();
        return self::$firstDSN['phptype'];
    }

    public static function getName()
    {
        if(!isset(self::$firstDSN)) self::setFirstDSN();
        return self::$firstDSN['database'];
    }

    private static function setFirstDSN()
    {
        $conn = self::$connections[0];
        self::$firstDSN = $conn->getDSN();
    }

    /**
     * Get a database connection
     *
     * @return object database connection object
     */
    public static function &getConn($index = 0) { return self::$connections[$index]; }

    // Overridden
    public static function getConnection($dsn, $flags = 0)
    {
        $conn = null;
        $conn = parent::getConnection($dsn, $flags);
        self::$connections[] =& $conn;
        self::$count++;
        return $conn;
    }
}
?>
