<?php
/**
 * Table Maintenance API
 *
 * NOTE: THIS SUBSYSTEM IS SCHEDULED FOR DEPRECATION. EXISTING CODE
 * DEPENDS ON IT, THAT IS WHY IT IS HERE. IF YOU ARE WRITING NEW CODE
 * USE THE METHODS IN xarDataDict.php. BOTH SUBSYSTEMS ARE NOT 100% FINISHED
 * BUT THIS ONE WILL BE ABANDONED, YOU MIGHT AS WELL WRITE YOUR CODE TO USE
 * THE MAINTAINED SUBSYSTEM.

 * @package core\database
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Gary Mitchell
 * @todo Check functions!
 * @todo Check FIXMEs
 * @todo Document functions
**/

/**
 * Public Functions:
 *
 * xarDBCreateDatabase($databaseName, $databaseType = NULL)
 * xarDBCreateTable($tableName, $fields, $databaseType = NULL)
 * xarDBDropTable($tableName, $databaseType = NULL)
 * xarDBAlterTable($tableName, $args, $databaseType = NULL)
 * xarDBCreateIndex($tableName, $index, $databaseType = NULL)
 * xarDBDropIndex($tableName, $databaseType = NULL)
 * xarDBCreateColumn($columnType, $args1, $args2, $databaseType = NULL)
 *
 */

/**
 * Generate the SQL to create a database
 *
 * @uses xarTableDDL::createDatabase()
 * @param string $databaseName
 * @param string $databaseType
 * @return string $databaseCharset
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateDatabase($databaseName, $databaseType=NULL, $databaseCharset='utf-8')
{
    // perform validations on input arguments
    if (empty($databaseName)) throw new EmptyParameterException('databaseName');
    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    switch($databaseType) {
        case 'mysqli':
        case 'pdomysqli':
        case 'oci8':
        case 'oci8po':
            $sql = 'CREATE DATABASE '. $databaseName . ' DEFAULT CHARACTER SET ' . $databaseCharset;
            break;
        case 'pgsql':
        case 'pdopgsql':
            $sql = 'CREATE DATABASE "'.$databaseName .'" ENCODING "' . $databaseCharset . '"';
            break;
        case 'sqlite3':
        case 'pdosqlite':
            // No such thing, its created automatically when it doesnt exist
            $sql ='';
            break;
        case 'mssql':
        case 'datadict':
            //sys::import('xaraya.tableddl.datadict');
            //$sql = xarDB__datadictCreateDatabase($databaseName);
            //break;
            throw new BadParameterException($databaseType,'Unsupported database type: "#(1)"');
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;

}

/**
 * Generate the SQL to create a table
 *
 * @uses xarTableDDL::createTable()
 * @param string $tableName the table to alter
 * @param array<mixed> $fields
 * @param string $databaseType the database type (optional)
 * @param string $charset the character set (optional)
 * @return string generated sql
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateTable($tableName, $fields, $databaseType="",$charset="")
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($fields)) throw new BadParameterException('fields','The #(1) parameter is not an array');
    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }
    if (empty($charset)) $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
    // set Dbtype to pdosqlite
    $middleware = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
    if ($middleware == 'PDO') {
        $databaseType = 'pdosqlite';
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysqli':
        case 'pdomysqli':
            sys::import('xaraya.tableddl.mysqli');
            $sql = xarDB__mysqlCreateTable($tableName, $fields, $charset);
            break;
        case 'pgsql':
        case 'pdopgsql':
            sys::import('xaraya.tableddl.postgres');
            $sql = xarDB__postgresqlCreateTable($tableName, $fields, $charset);
            break;
        case 'oci8':
        case 'oci8po':
            sys::import('xaraya.tableddl.oracle');
            $sql = xarDB__oracleCreateTable($tableName, $fields, $charset);
            break;
        case 'sqlite3':
        case 'pdosqlite':
            sys::import('xaraya.tableddl.sqlite');
            $sql = xarDB__sqliteCreateTable($tableName, $fields, $charset);
            break;
        case 'mssql':
        case 'datadict':
            //sys::import('xaraya.tableddl.datadict');
            //$sql = xarDB__datadictCreateTable($tableName, $fields, $charset);
            //break;
            throw new BadParameterException($databaseType,'Unsupported database type: "#(1)"');
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

/**
 * Alter database table
 *
 * @uses xarTableDDL::alterTable()
 * @param string $tableName the table to alter
 * @param array<string, mixed> $args
 * with
 *     $args['command'] command to perform on table(add,modify,drop,rename)
 *     $args['field'] name of column to alter
 *     $args['type'] column type
 *     $args['size'] size of column if varying data
 *     $args['default'] default value of data
 *     $args['null'] null or not null (true/false)
 *     $args['unsigned'] allow unsigned data (true/false)
 *     $args['increment'] auto incrementing files
 *     $args['primary_key'] primary key
 * @param string $databaseType the database type (optional)
 * @throws EmptyParameterException, BadParameterException
 * @return string generated sql
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBAlterTable($tableName, $args, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($args) || !isset($args['command'])) {
        throw new BadParameterException('args','Invalid parameter "args", it must be an array, and the "command" key must be set');
    }

    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysqli':
        case 'pdomysqli':
            sys::import('xaraya.tableddl.mysql');
            $sql = xarDB__mysqlAlterTable($tableName, $args);
        break;
        case 'pgsql':
        case 'pdopgsql':
            sys::import('xaraya.tableddl.postgres');
            $sql = xarDB__postgresqlAlterTable($tableName, $args);
            break;
        case 'oci8':
        case 'oci8po':
            sys::import('xaraya.tableddl.oracle');
            $sql = xarDB__oracleAlterTable($tableName, $args);
        break;
        case 'sqlite3':
        case 'pdosqlite':
            sys::import('xaraya.tableddl.sqlite');
            $sql = xarDB__sqliteAlterTable($tableName, $args);
        break;
        case 'mssql':
        case 'datadict':
            throw new BadParameterException($databaseType,'Unsupported database type: "#(1)"');
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

/**
 * Generate the SQL to delete a table
 *
 * @uses xarTableDDL::dropTable()
 * @param string $tableName the physical table name
 * @param ?string $databaseType the database type
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBDropTable($tableName, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    switch($databaseType) {
        case 'mysqli':
        case 'pdomysqli':
        case 'pgsql':
        case 'pdopgsql':
            $sql = 'DROP TABLE IF EXISTS '.$tableName;
        break;
        case 'oci8':
        case 'oci8po':
        case 'sqlite3':
        case 'pdosqlite':
            $sql = 'DROP TABLE '.$tableName;
        break;
        case 'mssql':
        case 'datadict':
            throw new BadParameterException($databaseType,'Unsupported database type: "#(1)"');
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

/**
 * Generate the SQL to create a table column
 *
 * @uses xarTableDDL::dropTable()
 * @param string $tableName the physical table name
 * @param ?string $databaseType the database type
 * @return string|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateColumn(string $columnType, array $args1=[], array $args2=[], $databaseType = NULL)
{
	// Special care needs to be taken with this arg, since it could have any numeric or char value
	$defaultExists = isset($args1['default']);
	// Make sure all args are present and non-null
	$params = ['size','unsigned','charset'];
	foreach ($params as $param) $args1[$param] ??= '';
	extract($args1);
	$params = ['id','name','required','auto'];
	foreach ($params as $param) $args2[$param] ??= '';
	extract($args2);
	// Also this one: allow for an override
	$databaseType ??= xarDB::getType();

    switch($databaseType) {
        case 'mysqli':
        case 'pdomysqli':
			$sql = $name;
    		switch($columnType) {
    			case 'text':
    				if ($size == '') {
    					$sql .= " TEXT";
    				} else {
    					$sql .= " VARCHAR";
    				}
    			break;
    			case 'number':
    				if ($size != '' && (int)$size > 3) {
    					$sql .= " INTEGER";
    				} else {
    					$sql .= " TINYINT";
    				}
    			break;
    			default:
					$nativeType = xarXMLInstaller::getNativeType($columnType);
					if ($nativeType == false) {
						$message = "Unknown columnType: $columnType";
						die($message);
					}
					$sql .= " " . $nativeType;
    			break;
    		}
    		if (!empty($size)) $sql .= '(' . $size . ')';
    		if ((bool)$unsigned) $sql .= ' UNSIGNED';
    		if (!empty($charset)) $sql .= ' CHARACTER SET ' . $charset;
    		if ((bool)$required) $sql .= ' NOT NULL';
			// Special care needs to be taken with this arg, since it could have any numeric or char value
    		if ($defaultExists) {
    			if (strtolower($default) == 'null') {
		    		$sql .= " DEFAULT NULL";
    			} else {
		    		$sql .= " DEFAULT '" . $default . "'";
    			}
    		}
    	break;
        case 'sqlite3':
        case 'pdosqlite':
        	// Do nothing here. We're letting XSL handle the column definitions
    	break;
        case 'pgsql':
        case 'pdopgsql':
			$sql = $name;
    		switch($columnType) {
    			case 'text':
    			case 'longvarchar':
    				if ($size == '') {
    					$sql .= " TEXT";
    				} else {
    					$sql .= " VARCHAR";
    				}
    			break;
    			case 'number':
    				if ($size != '' && (int)$size > 3) {
    					$sql .= " INTEGER";
    				} else {
    					$sql .= " SMALLINT";
    				}
    			break;
    			case 'blob':
    				$sql .= " BYTEA";
    			break;
    			default:
					$nativeType = xarXMLInstaller::getNativeType($columnType);
					if ($nativeType == false) {
						$message = "Unknown columnType: $columnType";
						die($message);
					}
					$sql .= " " . $nativeType;
    			break;
    		}
    		if (!empty($size) && ($columnType == 'text')) $sql .= '(' . $size . ')';
    		if ((bool)$required) $sql .= ' NOT NULL';
			// Special care needs to be taken with this arg, since it could have any numeric or char value
    		if ($defaultExists) {
    			if (strtolower($default) == 'null') {
		    		$sql .= " DEFAULT NULL";
    			} else {
		    		$sql .= " DEFAULT '" . $default . "'";
    			}
    		}
    	break;
        case 'oci8':
        case 'oci8po':
        break;
        case 'mssql':
        case 'datadict':
            throw new BadParameterException($databaseType,'Unsupported database type: "#(1)"');
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

/**
 * Generate the SQL to create a table index
 *
 * @uses xarTableDDL::createIndex()
 * @param string $tableName the physical table name
 * @param array<string, mixed> $index an array containing the index name, type and fields array
 * @param string $databaseType is an optional parameter to specify the database type
 * @return string|false the generated SQL statement, or false on failure
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateIndex($tableName, $index, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($index) || !is_array($index['fields']) || empty($index['name'])) {
        throw new BadParameterException('index','The parameter "#(1)" must be an array, the "fields" key inside it must be an array and the "name" key must be set).');
    }
    // default for unique
    if (!isset($index['unique'])) {
        $index['unique'] = false;
    }

    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }
    // set Dbtype to pdosqlite
    $middleware = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
    if ($middleware == 'PDO') {
        $databaseType = 'pdosqlite';
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysqli':
        case 'pdomysqli':
            if ($index['unique'] == true) {
                $sql = 'ALTER TABLE '.$tableName.' ADD UNIQUE '.$index['name'];
            } else {
                $sql = 'ALTER TABLE '.$tableName.' ADD INDEX '.$index['name'];
            }
            $sql .= ' ('.join(',', $index['fields']).')';
        break;
        case 'pgsql':
        case 'pdopgsql':
        case 'oci8':
        case 'oci8po':
        case 'sqlite3':
        case 'pdosqlite':
            if ($index['unique'] == true) {
                $sql = 'CREATE UNIQUE INDEX '.$index['name'].' ON '.$tableName;
            } else {
                $sql = 'CREATE INDEX '.$index['name'].' ON '.$tableName;
            }
            $sql .= ' ('.join(',', $index['fields']).')';
        break;

        case 'mssql':
        case 'datadict':
            throw new BadParameterException($databaseType,'Unsupported database type: "#(1)"');

        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}
/**
 * Generate the SQL to drop an index
 *
 * @uses xarTableDDL::dropIndex()
 * @param string $tableName
 * @param array<string, mixed> $index name a db index name
 * @param string $databaseType
 * @return string|false generated sql to drop an index
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBDropIndex($tableName, $index, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($index) ||  empty($index['name'])) {
        throw new BadParameterException('index','The parameter "#(1)" must be an array, the "fields" key inside it must be an array and the "name" key must be set).');
    }
    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    // set Dbtype to pdosqlite
    $middleware = xarSystemVars::get(sys::CONFIG, 'DB.Middleware');
    if ($middleware == 'PDO') {
        $databaseType = 'pdosqlite';
    }
    // Select the correct database type
    switch($databaseType) {
        case 'mysqli':
        case 'pdomysqli':
            $sql = 'ALTER TABLE '.$tableName.' DROP INDEX '.$index['name'];
            break;
        case 'pgsql':
        case 'pdopgsql':
        case 'oci8':
        case 'oci8po':
        case 'sqlite3':
        case 'pdosqlite':
            $sql = 'DROP INDEX '.$index['name'];
            break;
        case 'mssql':
        case 'datadict':
            throw new BadParameterException($databaseType,'Unsupported database type: "#(1)"');
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

class xarTableDDL extends xarObject
{
    public static function init()
    {
        return true;
    }
    public static function createDatabase($databaseName, $databaseType=NULL, $databaseCharset='utf-8')
    {
        return xarDBCreateDatabase($databaseName, $databaseType, $databaseCharset);
    }
    public static function createTable($tableName, $fields, $databaseType="",$charset="")
    {
        return xarDBCreateTable($tableName, $fields, $databaseType,$charset);
    }
    public static function alterTable($tableName, $args, $databaseType = NULL)
    {
        return xarDBAlterTable($tableName, $args, $databaseType);
    }
    public static function dropTable($tableName, $databaseType = NULL)
    {
        return xarDBDropTable($tableName, $databaseType);
    }
    static public function xarDBCreateColumn(string $columnType, array $args1=[], array $args2=[], $databaseType = NULL)
    {
        return xarDBCreateColumn($columnType, $args1, $args2, $databaseType);
    }
    public static function createIndex($tableName, $index, $databaseType = NULL)
    {
        return xarDBCreateIndex($tableName, $index, $databaseType);
    }
    public static function dropIndex($tableName, $index, $databaseType = NULL)
    {
        return xarDBDropIndex($tableName, $index, $databaseType);
    }
}

class xarXMLInstaller extends xarObject
{
    static private $typesObject;
    
    // No constructor yet. maybe later
    
    static private function transform($xmlFile, $xslAction='display', $xslFile=null)
    {
        if (!isset($xmlFile))
            throw new BadParameterException(xarML('No file to transform!'));

        // Get the database type from the connection
		$databaseType = xarDB::getType();
		switch ($databaseType) {
			case 'sqlite3':
			case 'pdosqlite':
				sys::import('creole.drivers.sqlite.SQLiteTypes');
				self::$typesObject = new SQLiteTypes;
				$databaseType = 'sqlite3';
			break;
			case 'mysqli':
			case 'pdomysqli':
				sys::import('creole.drivers.mysql.MySQLTypes');
				self::$typesObject = new MySQLTypes;
				$databaseType = 'mysqli';
			break;
			case 'pgsql':
			case 'pdopgsql':
				sys::import('creole.drivers.pgsql.PgSQLTypes');
				self::$typesObject = new PgSQLTypes;
				$databaseType = 'pgsql';
			break;
			default:
				throw new Exception(xarML("Unknown database type: '#(1)'", $databaseType));
		}
        
        if (!isset($xslFile))
            $xslFile = sys::lib() . 'xaraya/tableddl/xml2ddl-'. $databaseType . '.xsl';
        if (!file_exists($xslFile)) {
            $msg = xarML('The file #(1) was not found', $xslFile);
            throw new BadParameterException(null, $msg);
        }
        sys::import('xaraya.tableddl.xslprocessor');
        $xslProc = new XarayaXSLProcessor($xslFile);
        $xslProc->setParameter('', 'action', $xslAction);
        $xslProc->setParameter('', 'tableprefix', xarDB::getPrefix());
        return $xslProc->transform($xmlFile);
    }
    
		// For now we'll use Creoles list of types
        // TODO: Extend or change as more database types are added
        // TODO: Move the code to tableddl or...?
        // TODO: Do we still need to support both tabledll andf datadict?
        //       I don't see an inherent advantage/disadvantage either way, and the decision
        //       to use tabledll for the xsl stuff was one of convenience at the time.
    public static function getNativeType($creoleType)
    {
        sys::import('creole.CreoleTypes');
        $code = (int)CreoleTypes::getCreoleCode(strtoupper($creoleType));
        if (null == $code) die(xarML("Unknown Creole type: '#(1)'", $creoleType));
        if (null == $type = strtoupper(self::$typesObject::getNativeType($code))) die(xarML("Unknown Creole type: '#(1)'", $creoleType));
        return $type;
    }

    static public function createTable($tablefile, $module)
    {
        if (empty($module))
            throw new BadParameterException('Missing a module name to create for');
        if (empty($tablefile))
            throw new BadParameterException('Missing a XML file to create from');
            
        $xmlfile = sys::code() . 'modules/' . $module . '/xardata/' . $tablefile . '.xml';
        if (!file_exists($xmlfile)) {
            $msg = xarML('Could not find the file #(1) to create tables from', $xmlfile);
            throw new BadParameterException(null, $msg);
        }

        // Create a query string for table creation from the XML schema passed
        $sqlCode = self::transform($xmlfile, 'create');
        // Run the query code to add variable values (there aren't any) and execute any PHP snippets inserted by the transform 
        $sqlCode = xarTpl::string($sqlCode, []);
        // Turn the query string into an array of queries
        $queries = explode(';',$sqlCode);
        // The last element is empty: remove it
        array_pop($queries);

        // Execute each of the queries
        $dbconn = xarDB::getConn();
        foreach ($queries as $q) {
            xarLog::message('Executing SQL: ' . $q, xarLog::LEVEL_INFO);
            $dbconn->Execute($q);
        }
        return true;
    }
}
