<?php
/**
 * Purpose of file:  Data Dictionary API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * @subpackage Data Dictionary Module
 * @author Richard Cave <rcave@xaraya.com>
 */

/**
 * xarDataDict: class for the data dictionary
 *
 * Represents the repository for the Xaraya data dictionary 
 * For more information:
 *   http://phplens.com/lens/adodb/docs-datadict.htm
 *
 * @author Richard Cave <rcave@xaraya.com>
 * @access public
 * @throws none
 */
class xarDataDict 
{
    var $dict;

    /**
     * xarDataDict: constructor for the class
     *
     * Initializes variables for xarDataDict class 
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   object $dbconn Database connection
     * @return  none
     * @throws  none
    */
    function xarDataDict($dbconn) 
    {
        // Check if we passed in a database connection.
        if (empty($dbconn)) {
            // Get current database connection
            $dbconn =& xarDBGetConn();
        }

        // Create new data dictionary
        $this->dict = NewDataDictionary($dbconn);
    }

    /**
     * addColumn
     *
     * Add one or more columns
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $table name of the table
     * @param   $fields string or array containing column info
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function addColumn($table, $fields)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (empty($fields)) {
            $invalid[] = 'fields';
        }
        if (count($invalid) > 0) {
            $msg = xarML('Invalid #(1) for function #(2)() in #(3)',
                    join(', ',$invalid), 'addColumn', 'xarDataDict');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return false;
        }

        // Generate SQL to add a column to the table
        $sql = $this->dict->AddColumnSQL($table, $fields);
        if (!$sql)
            return false;

        // Execute the resulting SQL
        $result = $this->executeSQLArray($sql);

        return $result;
    }

    /**
     * alterColumn
     *
     * Alters a column in a table
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $table name of the table
     * @param   $fields string or array containing column info
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function alterColumn($table, $fields)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (empty($fields)) {
            $invalid[] = 'fields';
        }
        if (count($invalid) > 0) {
            $msg = xarML('Invalid #(1) for function #(2)() in #(3)',
                    join(', ',$invalid), 'alterColumn', 'xarDataDict');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return false;
        }

        // Generate SQL to change the column in the table
        $sql = $this->dict->AlterColumnSQL($table, $fields);
        if (!$sql)
            return false;

        // Execute the resulting SQL
        $result = $this->executeSQLArray($sql);

        return $result;
    }

    /**
     * alterTable
     *
     * Alters a table.
     *
     * Alias for changeTable() at the moment, but that may change if
     * we want to use this function to execute explicit ALTER TABLE
     * statements rather than driving from the table meta data.
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $table name of the table
     * @param   $fields string or array containing field info
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function alterTable($table, $fields)
    {
        return $this->changeTable($table, $fields);
    }

    /**
     * changeTable
     *
     * Calls ADODB changeTable()
     * Alters a table with appropriate ALTER TABLE MODIFY COLUMN or
     * ALTER TABLE ADD $column if the column does not exist 
     *
     * $fields = "xar_name C(100) NOTNULL";
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $table name of the table
     * @param   $fields string or array containing field info
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function changeTable($table, $fields)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (empty($fields)) {
            $invalid[] = 'fields';
        }
        if (count($invalid) > 0) {
            $msg = xarML(
                'Invalid #(1) for function #(2)() in #(3)',
                join(', ',$invalid), 'changeTable', 'xarDataDict'
            );
            xarErrorSet(
                XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                new SystemException(__FILE__.'('.__LINE__.'): ' . $msg)
            );
            return false;
        }

        // Generate SQL to change the table
        $sql = $this->dict->ChangeTableSQL($table, $fields);
        if (!$sql) {
            $result = false;
        } else {
            // Execute the resulting SQL
            $result = $this->executeSQLArray($sql);
        }

        return $result;
    }

    /**
     * createDatabase
     *
     * Create a database 
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $database database name to create
     * @param   $options array containing database options
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function createDatabase($database, $options = false)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($database)) {
            $invalid[] = 'database';
        }
        if (count($invalid) > 0) {
            $msg = xarML('Invalid #(1) for function #(2)() in #(3)',
                    join(', ',$invalid), 'createDatabase', 'xarDataDict');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return false;
        }

        // Generate SQL to create the database
        $sql = $this->dict->CreateDatabase($database, $options);
        if (!$sql) {
            $result = false;
        } else {
            // Execute the resulting SQL - don't continue on error
            $result = $this->executeSQLArray($sql, false);
        }

        return $result;
    }

    /**
     * createIndex
     *
     * Create an index
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $index name of the index
     * @param   $table name of the table
     * @param   $fields string or array containing field info
     * @param   $options array containing index creation options
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function createIndex($index, $table, $fields, $options = false)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($index)) {
            $invalid[] = 'index name';
        }
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (empty($fields)) {
            $invalid[] = 'fields';
        }
        if (count($invalid) > 0) {
            $msg = xarML(
                'Invalid #(1) for function #(2)() in #(3)',
                join(', ',$invalid), 'createIndex', 'xarDataDict'
            );
            xarErrorSet(
                XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                new SystemException(__FILE__.'('.__LINE__.'): '.$msg)
            );
            return false;
        }

        // Generate SQL to create the index
        $sql = $this->dict->CreateIndexSQL($index, $table, $fields, $options);
        if (!$sql) {
            $result = false;
        } else {
            // Execute the resulting SQL
            $result = $this->executeSQLArray($sql);
        }

        return $result;
    }

    /**
     * createIndex
     *
     * Drop an index
     *
     * @author  Jason Judge <judgej@xaraya.com>
     * @access  public
     * @param   $index name of the index
     * @param   $table name of the table
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function dropIndex($index, $table)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($index)) {
            $invalid[] = 'index name';
        }
        if (empty($table)) {
            $table = NULL;
        }
        if (count($invalid) > 0) {
            $msg = xarML(
                'Invalid #(1) for function #(2)() in #(3)',
                join(', ',$invalid), 'dropIndex', 'xarDataDict'
            );
            xarErrorSet(
                XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                new SystemException(__FILE__.'('.__LINE__.'): '.$msg)
            );
            return false;
        }

        // Generate SQL to drop the index
        $sql = $this->dict->DropIndexSQL($index, $table);
        if (!$sql) {
            $result = false;
        } else {
            // Execute the resulting SQL
            $result = $this->executeSQLArray($sql);
        }

        return $result;
    }

    /**
     * createTable
     *
     * Create a table 
     * ADOdb uses a portable declarative data dictionary format similar to SQL.
     * Field types use 1 character codes, and fields are separated by commas.
     * The following example creates three fields: "col1", "col2" and "col3":
     * $flds = " 
     *     col1 C(32) NOTNULL DEFAULT 'abc',
     *     col2 I  DEFAULT 0,
     *     col3 N(12.2)
     * ";
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $table name of the table
     * @param   $fields string or array containing field info
     * @param   $options array containing table creationg options
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function createTable($table, $fields, $options = false)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (empty($fields)) {
            $invalid[] = 'fields';
        }
        if (count($invalid) > 0) {
            $msg = xarML('Invalid #(1) for function #(2)() in #(3)',
                    join(', ',$invalid), 'createTable', 'xarDataDict');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return false;
        }

        // Generate SQL to create the table
        $sql = $this->dict->CreateTableSQL($table, $fields, $options);
        if (!$sql)
            return false;

        // Execute the resulting SQL
        $result = $this->executeSQLArray($sql);

        return $result;
    }

    /**
     * dropColumn
     *
     * Drop one or more columns
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $table name of the table
     * @param   $fields string or array containing column info
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function dropColumn($table, $fields)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (empty($fields)) {
            $invalid[] = 'fields';
        }
        if (count($invalid) > 0) {
            $msg = xarML('Invalid #(1) for function #(2)() in #(3)',
                    join(', ',$invalid), 'dropColumn', 'xarDataDict');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return false;
        }

        // Generate SQL to drop the column
        $sql = $this->dict->DropColumnSQL($table, $fields);
        if (!$sql)
            return false;

        // Execute the resulting SQL
        $result = $this->executeSQLArray($sql);

        return $result;
    }

    /**
     * dropTable
     *
     * Drop a table 
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $table name of the table
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function dropTable($table)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (count($invalid) > 0) {
            $msg = xarML('Invalid #(1) for function #(2)() in #(3)',
                    join(', ',$invalid), 'dropTable', 'xarDataDict');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return false;
        }

        // Generate SQL to drop the table
        $sql = $this->dict->DropTableSQL($table);
        if (!$sql)
            return false;

        // Execute the resulting SQL
        $result = $this->executeSQLArray($sql);

        return $result;
    }

    /**
     * executeSQLArray
     *
     * Execute an array of SQL strings 
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   $sql an array of SQL strings 
     * @param   $contOnError continue on error, default is true
     * @returns 0 if failed, 1 if executed with errors, 2 if successful
     * @return  integer
     * @throws  none
    */
    function executeSQLArray($sql, $contOnError = true)
    {
        // Execute the SQL command
        $result = $this->dict->ExecuteSQLArray($sql, $contOnError);

        return $result;
    }

    /**
     * getColumns
     *
     * Retrieve all the columns for a table
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  returns an array of ADOFieldObject's, one field
     *          object for every column of $table, false otherwise
     * @throws  none
    */
    function getColumns($table)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (count($invalid) > 0) {
            $msg = xarML('Invalid #(1) for function #(2)() in #(3)',
                    join(', ',$invalid), 'getColumns', 'xarDataDict');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return false;
        }

        $columns = $this->dict->MetaColumns($table);
        return $columns;
    }

    /**
     * getPrimaryKeys
     *
     * Retrieve all the primary keys for a table
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  array of primary keys for the table, false otherwise
     * @throws  none
    */
    function getPrimaryKeys($table)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (count($invalid) > 0) {
            $msg = xarML('Invalid #(1) for function #(2)() in #(3)',
                    join(', ',$invalid), 'getPrimaryKeys', 'xarDataDict');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return false;
        }

        $keys = $this->dict->MetaPrimaryKeys($table);
        return $keys;
    }

    /**
     * getIndexes
     *
     * Retrieve all indexes for a table
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   table name of table
     * @param   primary boolean include primary keys (default false)
     * @returns array on success, false on failure
     * @return  returns an array of ADODB index arrays or false if none
     * @throws  none
    */
    function getIndexes($table, $primary = false)
    {
        // Perform validation on input arguments
        $invalid = array();
        if (empty($table)) {
            $invalid[] = 'table name';
        }
        if (count($invalid) > 0) {
            $msg = xarML(
                'Invalid #(1) for function #(2)() in #(3)',
                join(', ',$invalid), 'getIndexes', 'xarDataDict'
            );
            xarErrorSet(
                XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                new SystemException(__FILE__.'('.__LINE__.'): ' . $msg)
            );
            return false;
        }

        return $this->dict->MetaIndexes($table, $primary);
    }

    /**
     * getTables
     *
     * Retrieve all the tables in a database
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   xartables true if only Xaraya tables, false for all tables
     * @returns array on success, false on failure
     * @return  array of tables available in the database, false otherwise
     * @throws  none
     * @todo    flag for Xaraya system vs site tables
    */
    function getTables($xartables = true)
    {
        if ($xartables) {
            // Retrieve only Xaraya system tables
            $tables = $this->getSystemTables();
            if (!$tables) {
                return false;
            }
        } else { 
            $tables = $this->dict->MetaTables();

            if (!isset($tables)) {
                return false;
            }

            // Sort tables
            sort($tables);
        }
        return $tables;
    }

    /**
     * getSystemTables
     *
     * Retrieve all the Xaraya system tables in a database
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  array of tables available in the database, false otherwise
     * @throws  none
    */
    function getSystemTables()
    {
        $metatables = $this->dict->MetaTables();
        if (!isset($metatables)) {
            return false;
        }

        // Sort tables
        sort($metatables);

        // Since mask only works for a few databases when
        // retrieving with MetaTables, parse out the tables
        // based on the system table prefix
        $tables = array();
        $systemPrefix = xarDBGetSystemTablePrefix();
        $prefixLength = strlen($systemPrefix);

        if ($prefixLength > 0) {
            foreach ($metatables as $metatable) {
                // Check for system prefix
                if (strncmp($systemPrefix, $metatable, $prefixLength) == 0)
                    $tables[] = $metatable;
            }
        } else {
            $tables = $metatables;
        }

        return $tables;
    }

    /**
     * getSiteTables
     *
     * Retrieve all the Xaraya site tables in a database
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  array of tables available in the database, false otherwise
     * @throws  none
    */
    function getSiteTables()
    {
        $metatables = $this->dict->MetaTables();
        if (!isset($metatables)) {
            return false;
        }

        // Sort tables
        sort($metatables);

        // Since mask only works for a few databases when
        // retrieving with MetaTables, parse out the tables
        // based on the system table prefix
        $tables = array();
    
        // Currently, xarDBGetSiteTablePrefix() returns the same prefix
        // as xarDBGetSystemTablePrefix()
        $systemPrefix = xarDBGetSiteTablePrefix(); 
        $prefixLength = strlen($systemPrefix);

        if ($prefixLength > 0) {
            foreach ($metatables as $metatable) {
                // Check for system prefix
                if (strncmp($systemPrefix, $metatable, $prefixLength) == 0)
                    $tables[] = $metatable;
            }
        } else {
            $tables = $metatables;
        }

        return $tables;
    }

    /**
     * getTableDefinitions
     *
     * Retrieve the column names and information for a table
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   xartables true if only Xaraya tables, false for all tables
     * @returns array on success, false on failure
     * @return  array of columns names for the table, false otherwise
     * @throws  none
     * @todo    flag for Xaraya system vs site tables
    */
    function getTableDefinitions($xartables = true)
    {
        if ($xartables) {
            // Retrieve Xaraya system tables
            $tables = $this->getSystemTables();
            if (!$tables) {
                return false;
            }
        } else { 
            // Get all the tables 
            $tables = $this->getTables();
            if (!$tables) {
                return false;
            }
        }

        $tableDefs = array();
        foreach ($tables as $table) {
            $columnDefs = array();

            // Get the columns for each table
            $columns = $this->getColumns($table);
            foreach ($columns as $column) {
                // Retrieve values returned from getColumns
                $name           = $column->name;
                $max_length     = $column->max_length;
                $type           = $column->type;
                $not_null       = $column->not_null;
                $has_default    = $column->has_default;
                if ($has_default) {
                    $default_value  = $column->default_value;
                }

                // Optional fields
                if (isset($column->primary_key))
                    $primary_key = $column->primary_key;
                else
                    $primary_key = false;

                if (isset($column->unique))
                    $unique = $column->unique;
                else
                    $unique = false;

                if (isset($column->binary))
                    $binary = $column->binary;
                else
                    $binary = false;

                if (isset($column->auto_increment))
                    $auto_increment = $column->auto_increment;
                else
                    $auto_increment = false;

                // Assign columns.  Keys are different names as they
                // must correspond to the existing xar_tables columns.
                $columnDefs[$name] = array(
                    'table'       => $table,
                    'field'       => $name,
                    'type'        => $type,
                    'size'        => $max_length,
                    'has_default' => $has_default,
                    'binary'      => $binary,
                    'null'        => $not_null,
                    'increment'   => $auto_increment,
                    'primary_key' => $primary_key);
                if ($has_default) {
                    $columnDefs[$name]['default'] = $default_value;
                }
            }

            // Assign column definitions to table
            $tableDefs[$table] = $columnDefs;
        }

        return $tableDefs;
    }
}


/**
 * xarMetaData: class for the database metadata
 *
 * Represents the repository containing metadata 
 *
 * @author Richard Cave <rcave@xaraya.com>
 * @access public
 * @throws none
 */
class xarMetaData 
{
    var $dbconn;

    /**
     * xarMetaData: constructor for the class
     *
     * Initializes variables for xarMetaData class 
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   object $dbconn database connection (optional)
     * @return  none
     * @throws  none
    */
    function xarMetaData($dbconn = false) 
    {
        // Check if we passed in a database connection.
        // TODO: check heritage of the object to be sure it is a DB connection.
        if (empty($dbconn) || !is_object($dbconn)) {
            // Get current default database connection
            $dbconn =& xarDBGetConn();
        }

        $this->dbconn =& $dbconn;
    }
   
    
    /**
     * getDatabases
     *
     * Retrieve all the databases
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  array of databases available on the server, false otherwise
     * @throws  none
     * @todo    make this work for all database types
    */
    function getDatabases()
    {
        // Only available for ODBC, MySQL and ADO
        $databases = $this->dbconn->MetaDatabases();
        if (!isset($databases)) {
            return;
        }
        return $databases;
    }


    /**
     * getTables
     *
     * Retrieve all the tables in a database
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  array of tables available in the database, false otherwise
     * @throws  none
    */
    function getTables($type='TABLES')
    {
        $tables = $this->dbconn->MetaTables($type);
        if (!isset($tables)) {
            return;
        }

        // Sort tables
        sort($tables);

        return $tables;
    }

    /**
     * getColumns
     *
     * Retrieve all the columns for a table
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  returns an array of ADOFieldObject's, one field
     *          object for every column of $table, false otherwise
     * @throws  none
    */
    function getColumns($table)
    {
        $columns = $this->dbconn->MetaColumns($table);
        if (!isset($columns)) {
            return;
        }
        return $columns;
    }

    /**
     * getColumnNames
     *
     * Retrieve all the column names for a table
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  array of columns names for the table, false otherwise
     * @throws  none
    */
    function getColumnNames($table)
    {
        $column_names = $this->dbconn->MetaColumnNames($table);
        if (!isset($column_names)) {
            return;
        }
        return $column_names;
    }

    /**
     * getPrimaryKeys
     *
     * Retrieve all the primary keys for a table
     *
     * @author  Richard Cave <rcave@xaraya.com>
     * @access  public
     * @param   none
     * @returns array on success, false on failure
     * @return  array of primary keys for the table, false otherwise
     * @throws  none
    */
    function getPrimaryKeys($table)
    {
        $keys = $this->dbconn->MetaPrimaryKeys($table);
        if (!isset($keys)) {
            return;
        }
        return $keys;
    }

}


?>
