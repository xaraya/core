<?php

/*
 * xaraya wrapper class for Creole
 *
 *
 */
sys::import('creole.Creole');
class xarDB extends Creole 
{
    public static $count = 0;

    // Instead of the globals, we save our db info here.
    private static $firstDSN = null;
    private static $connections = array();
    private static $tables = array();

    public static function &getTables() 
    {
        return self::$tables;
    }

    public static function importTables($tables = array())
    {
        assert('is_array($tables)');
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

    public static function &getConn($index)
    {
        return self::$connections[$index];
    }

    // Overridden
    public static function getConnection($dsn, $flags = 0)
    {
        $conn = parent::getConnection($dsn, $flags);
        self::$connections[] =& $conn;
        self::$count++;
        return $conn;
    }

}

?>
