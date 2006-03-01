<?php

/*
 * xaraya wrapper class for Creole
 *
 *
 */
include_once 'Creole.php';
class xarDB extends Creole 
{
    // Instead of the superglobal, save our connections here.
    public static $connections = array();
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
}

?>
