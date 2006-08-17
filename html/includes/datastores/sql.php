<?php
/**
 * Base class for SQL Data Stores
 *
 * @package dynamicdata
 * @subpackage datastores
**/

/**
 * Base class for SQL Data Stores
 *
 * @package dynamicdata
**/
class Dynamic_SQL_DataStore extends Dynamic_DataStore
{
    protected $db     = null;
    protected $tables = null;
    
    function __construct($name)
    {
        parent::__construct($name);
        $this->db     = xarDBGetConn();
        $this->tables = xarDBGetTables(); // Is this scopy enough? i.e. would all tables be there already?
    }
}

?>