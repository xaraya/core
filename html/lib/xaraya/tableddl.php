<?php

/**
 * Table Maintenance API
 *
 * @package core\database
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Gary Mitchell
 * @todo Check functions!
 * @todo Check FIXMEs
 * @todo Document functions
**/

use Xaraya\Services\xar;

/**
 * Public Functions:
 *
 * xarTableDDL::createDatabase($databaseName, $databaseType = NULL)
 * xarTableDDL::createTable($tableName, $fields, $databaseType = NULL)
 * xarTableDDL::dropTable($tableName, $databaseType = NULL)
 * xarTableDDL::alterTable($tableName, $args, $databaseType = NULL)
 * xarTableDDL::createIndex($tableName, $index, $databaseType = NULL)
 * xarTableDDL::dropIndex($tableName, $databaseType = NULL)
 * xarTableDDL::createColumn($columnType, $args1, $args2, $databaseType = NULL)
 *
 */

/**
 * @deprecated 2.4.1 use xarTableDDL::createDatabase() instead
 * @uses xarTableDDL::createDatabase()
 */
function xarDBCreateDatabase($databaseName, $databaseType = null, $databaseCharset = 'utf-8')
{
    return xarTableDDL::createDatabase($databaseName, $databaseType, $databaseCharset);
}

/**
 * @deprecated 2.4.1 use xarTableDDL::createTable() instead
 * @uses xarTableDDL::createTable()
 */
function xarDBCreateTable($tableName, $fields, $databaseType = "", $charset = "")
{
    return xarTableDDL::createTable($tableName, $fields, $databaseType, $charset);
}

/**
 * @deprecated 2.4.1 use xarTableDDL::alterTable() instead
 * @uses xarTableDDL::alterTable()
 */
function xarDBAlterTable($tableName, $args, $databaseType = null)
{
    return xarTableDDL::alterTable($tableName, $args, $databaseType);
}

/**
 * @deprecated 2.4.1 use xarTableDDL::dropTable() instead
 * @uses xarTableDDL::dropTable()
 */
function xarDBDropTable($tableName, $databaseType = null)
{
    return xarTableDDL::dropTable($tableName, $databaseType);
}

/**
 * @deprecated 2.4.1 use xarTableDDL::createColumn() instead
 * @uses xarTableDDL::createColumn()
 */
function xarDBCreateColumn(string $columnType, array $args1 = [], array $args2 = [], $databaseType = null)
{
    return xarTableDDL::createColumn($columnType, $args1, $args2, $databaseType);
}

/**
 * @deprecated 2.4.1 use xarTableDDL::createIndex() instead
 * @uses xarTableDDL::createIndex()
 */
function xarDBCreateIndex($tableName, $index, $databaseType = null)
{
    return xarTableDDL::createIndex($tableName, $index, $databaseType);
}

/**
 * @deprecated 2.4.1 use xarTableDDL::dropIndex() instead
 * @uses xarTableDDL::dropIndex()
 */
function xarDBDropIndex($tableName, $index, $databaseType = null)
{
    return xarTableDDL::dropIndex($tableName, $index, $databaseType);
}

/**
 * Table Maintenance API
 */
class xarTableDDL extends xarObject
{
    public static function init()
    {
        return true;
    }

    /**
     * Generate the SQL to create a database
     *
     * @param string $databaseName
     * @param string $databaseType
     * @return string $databaseCharset
     * @throws EmptyParameterException, BadParameterException
     */
    public static function createDatabase($databaseName, $databaseType = null, $databaseCharset = 'utf-8')
    {
        // perform validations on input arguments
        if (empty($databaseName)) {
            throw new EmptyParameterException('databaseName');
        }
        if (empty($databaseType)) {
            $databaseType = xar::db()->getType();
        }

        switch ($databaseType) {
            case 'mysqli':
            case 'pdomysqli':
            case 'oci8':
            case 'oci8po':
                $sql = 'CREATE DATABASE ' . $databaseName . ' DEFAULT CHARACTER SET ' . $databaseCharset;
                break;
            case 'pgsql':
            case 'pdopgsql':
                $sql = 'CREATE DATABASE "' . $databaseName . '" ENCODING "' . $databaseCharset . '"';
                break;
            case 'sqlite3':
            case 'pdosqlite':
                // No such thing, its created automatically when it doesnt exist
                $sql = '';
                break;
            case 'mssql':
            case 'datadict':
                throw new BadParameterException($databaseType, 'Unsupported database type: "#(1)"');
                // Other DBs go here
            default:
                throw new BadParameterException($databaseType, 'Unknown database type: "#(1)"');
        }
        return $sql;
    }

    /**
     * Generate the SQL to create a table
     *
     * @param string $tableName the table to alter
     * @param array<mixed> $fields
     * @param string $databaseType the database type (optional)
     * @param string $charset the character set (optional)
     * @return string generated sql
     * @throws EmptyParameterException, BadParameterException
     */
    public static function createTable($tableName, $fields, $databaseType = "", $charset = "")
    {
        // perform validations on input arguments
        if (empty($tableName)) {
            throw new EmptyParameterException('tableName');
        }
        if (!is_array($fields)) {
            throw new BadParameterException('fields', 'The #(1) parameter is not an array');
        }
        if (empty($databaseType)) {
            $databaseType = xar::db()->getType();
        }
        if (empty($charset)) {
            $charset = xar::sysConfig()->getVar('DB.Charset');
        }
        // set Dbtype to pdosqlite
        $middleware = xar::sysConfig()->getVar('DB.Middleware');
        if ($middleware == 'PDO') {
            $databaseType = 'pdosqlite';
        }

        // Select the correct database type
        switch ($databaseType) {
            case 'mysqli':
            case 'pdomysqli':
                $sql = \Xaraya\Database\TableDDL\MysqliDDL::createTable($tableName, $fields, $charset);
                break;
            case 'pgsql':
            case 'pdopgsql':
                $sql = \Xaraya\Database\TableDDL\PostgresDDL::createTable($tableName, $fields, $charset);
                break;
            case 'oci8':
            case 'oci8po':
                $sql = \Xaraya\Database\TableDDL\OracleDDL::createTable($tableName, $fields, $charset);
                break;
            case 'sqlite3':
            case 'pdosqlite':
                $sql = \Xaraya\Database\TableDDL\SqliteDDL::createTable($tableName, $fields, $charset);
                break;
            case 'mssql':
            case 'datadict':
                throw new BadParameterException($databaseType, 'Unsupported database type: "#(1)"');
                // Other DBs go here
            default:
                throw new BadParameterException($databaseType, 'Unknown database type: "#(1)"');
        }
        return $sql;
    }

    /**
     * Alter database table
     *
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
     */
    public static function alterTable($tableName, $args, $databaseType = null)
    {
        // perform validations on input arguments
        if (empty($tableName)) {
            throw new EmptyParameterException('tableName');
        }
        if (!is_array($args) || !isset($args['command'])) {
            throw new BadParameterException('args', 'Invalid parameter "args", it must be an array, and the "command" key must be set');
        }

        if (empty($databaseType)) {
            $databaseType = xar::db()->getType();
        }

        // Select the correct database type
        switch ($databaseType) {
            case 'mysqli':
            case 'pdomysqli':
                $sql = \Xaraya\Database\TableDDL\MysqliDDL::alterTable($tableName, $args);
                break;
            case 'pgsql':
            case 'pdopgsql':
                $sql = \Xaraya\Database\TableDDL\PostgresDDL::alterTable($tableName, $args);
                break;
            case 'oci8':
            case 'oci8po':
                $sql = \Xaraya\Database\TableDDL\OracleDDL::alterTable($tableName, $args);
                break;
            case 'sqlite3':
            case 'pdosqlite':
                $sql = \Xaraya\Database\TableDDL\SqliteDDL::alterTable($tableName, $args);
                break;
            case 'mssql':
            case 'datadict':
                throw new BadParameterException($databaseType, 'Unsupported database type: "#(1)"');
                // Other DBs go here
            default:
                throw new BadParameterException($databaseType, 'Unknown database type: "#(1)"');
        }
        return $sql;
    }

    /**
     * Generate the SQL to delete a table
     *
     * @param string $tableName the physical table name
     * @param ?string $databaseType the database type
     * @return string|false the generated SQL statement, or false on failure
     */
    public static function dropTable($tableName, $databaseType = null)
    {
        // perform validations on input arguments
        if (empty($tableName)) {
            throw new EmptyParameterException('tableName');
        }
        if (empty($databaseType)) {
            $databaseType = xar::db()->getType();
        }

        switch ($databaseType) {
            case 'mysqli':
            case 'pdomysqli':
            case 'pgsql':
            case 'pdopgsql':
                $sql = 'DROP TABLE IF EXISTS ' . $tableName;
                break;
            case 'oci8':
            case 'oci8po':
            case 'sqlite3':
            case 'pdosqlite':
                $sql = 'DROP TABLE ' . $tableName;
                break;
            case 'mssql':
            case 'datadict':
                throw new BadParameterException($databaseType, 'Unsupported database type: "#(1)"');
                // Other DBs go here
            default:
                throw new BadParameterException($databaseType, 'Unknown database type: "#(1)"');
        }
        return $sql;
    }

    /**
     * Generate the SQL to create a table column
     *
     * @param string $tableName the physical table name
     * @param ?string $databaseType the database type
     * @return string|false the generated SQL statement, or false on failure
     */
    public static function createColumn(string $columnType, array $args1 = [], array $args2 = [], $databaseType = null)
    {
        // Special care needs to be taken with this arg, since it could have any numeric or char value
        $defaultExists = isset($args1['default']);
        // Make sure all args are present and non-null
        $params = ['size','unsigned','charset'];
        foreach ($params as $param) {
            $args1[$param] ??= '';
        }
        extract($args1);
        $params = ['id','name','required','auto'];
        foreach ($params as $param) {
            $args2[$param] ??= '';
        }
        extract($args2);
        // Also this one: allow for an override
        $databaseType ??= xar::db()->getType();

        switch ($databaseType) {
            case 'mysqli':
            case 'pdomysqli':
                $sql = $name;
                switch ($columnType) {
                    case 'text':
                        if ($size == '') {
                            $sql .= " TEXT";
                        } else {
                            $sql .= " VARCHAR";
                        }
                        break;
                    case 'number':
                        if ($size != '' && (int) $size > 3) {
                            $sql .= " INTEGER";
                        } else {
                            $sql .= " TINYINT";
                        }
                        break;
                    default:
                        $nativeType = xarXMLInstaller::getNativeType($columnType);
                        if ($nativeType == false) {
                            $message = "Unknown columnType: $columnType";
                            xarCore::exit($message);
                            return false;
                        }
                        $sql .= " " . $nativeType;
                        break;
                }
                if (!empty($size)) {
                    $sql .= '(' . $size . ')';
                }
                if ((bool) $unsigned) {
                    $sql .= ' UNSIGNED';
                }
                if (!empty($charset)) {
                    $sql .= ' CHARACTER SET ' . $charset;
                }
                if ((bool) $required) {
                    $sql .= ' NOT NULL';
                }
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
                switch ($columnType) {
                    case 'text':
                    case 'longvarchar':
                        if ($size == '') {
                            $sql .= " TEXT";
                        } else {
                            $sql .= " VARCHAR";
                        }
                        break;
                    case 'number':
                        if ($size != '' && (int) $size > 3) {
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
                            xarCore::exit($message);
                            return false;
                        }
                        $sql .= " " . $nativeType;
                        break;
                }
                if (!empty($size) && ($columnType == 'text')) {
                    $sql .= '(' . $size . ')';
                }
                if ((bool) $required) {
                    $sql .= ' NOT NULL';
                }
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
                throw new BadParameterException($databaseType, 'Unsupported database type: "#(1)"');
                // Other DBs go here
            default:
                throw new BadParameterException($databaseType, 'Unknown database type: "#(1)"');
        }
        return $sql;
    }

    /**
     * Generate the SQL to create a table index
     *
     * @param string $tableName the physical table name
     * @param array<string, mixed> $index an array containing the index name, type and fields array
     * @param string $databaseType is an optional parameter to specify the database type
     * @return string|false the generated SQL statement, or false on failure
     * @throws EmptyParameterException, BadParameterException
     */
    public static function createIndex($tableName, $index, $databaseType = null)
    {
        // perform validations on input arguments
        if (empty($tableName)) {
            throw new EmptyParameterException('tableName');
        }
        if (!is_array($index) || !is_array($index['fields']) || empty($index['name'])) {
            throw new BadParameterException('index', 'The parameter "#(1)" must be an array, the "fields" key inside it must be an array and the "name" key must be set).');
        }
        // default for unique
        if (!isset($index['unique'])) {
            $index['unique'] = false;
        }

        if (empty($databaseType)) {
            $databaseType = xar::db()->getType();
        }
        // set Dbtype to pdosqlite
        $middleware = xar::sysConfig()->getVar('DB.Middleware');
        if ($middleware == 'PDO') {
            $databaseType = 'pdosqlite';
        }

        // Select the correct database type
        switch ($databaseType) {
            case 'mysqli':
            case 'pdomysqli':
                if ($index['unique'] == true) {
                    $sql = 'ALTER TABLE ' . $tableName . ' ADD UNIQUE ' . $index['name'];
                } else {
                    $sql = 'ALTER TABLE ' . $tableName . ' ADD INDEX ' . $index['name'];
                }
                $sql .= ' (' . join(',', $index['fields']) . ')';
                break;
            case 'pgsql':
            case 'pdopgsql':
            case 'oci8':
            case 'oci8po':
            case 'sqlite3':
            case 'pdosqlite':
                if ($index['unique'] == true) {
                    $sql = 'CREATE UNIQUE INDEX ' . $index['name'] . ' ON ' . $tableName;
                } else {
                    $sql = 'CREATE INDEX ' . $index['name'] . ' ON ' . $tableName;
                }
                $sql .= ' (' . join(',', $index['fields']) . ')';
                break;

            case 'mssql':
            case 'datadict':
                throw new BadParameterException($databaseType, 'Unsupported database type: "#(1)"');

                // Other DBs go here
            default:
                throw new BadParameterException($databaseType, 'Unknown database type: "#(1)"');
        }
        return $sql;
    }

    /**
     * Generate the SQL to drop an index
     *
     * @param string $tableName
     * @param array<string, mixed> $index name a db index name
     * @param string $databaseType
     * @return string|false generated sql to drop an index
     * @throws EmptyParameterException, BadParameterException
     */
    public static function dropIndex($tableName, $index, $databaseType = null)
    {
        // perform validations on input arguments
        if (empty($tableName)) {
            throw new EmptyParameterException('tableName');
        }
        if (!is_array($index) ||  empty($index['name'])) {
            throw new BadParameterException('index', 'The parameter "#(1)" must be an array, the "fields" key inside it must be an array and the "name" key must be set).');
        }
        if (empty($databaseType)) {
            $databaseType = xar::db()->getType();
        }

        // set Dbtype to pdosqlite
        $middleware = xar::sysConfig()->getVar('DB.Middleware');
        if ($middleware == 'PDO') {
            $databaseType = 'pdosqlite';
        }
        // Select the correct database type
        switch ($databaseType) {
            case 'mysqli':
            case 'pdomysqli':
                $sql = 'ALTER TABLE ' . $tableName . ' DROP INDEX ' . $index['name'];
                break;
            case 'pgsql':
            case 'pdopgsql':
            case 'oci8':
            case 'oci8po':
            case 'sqlite3':
            case 'pdosqlite':
                $sql = 'DROP INDEX ' . $index['name'];
                break;
            case 'mssql':
            case 'datadict':
                throw new BadParameterException($databaseType, 'Unsupported database type: "#(1)"');
                // Other DBs go here
            default:
                throw new BadParameterException($databaseType, 'Unknown database type: "#(1)"');
        }
        return $sql;
    }
}

class xarXMLInstaller extends xarObject
{
    private static $typesObject;

    // No constructor yet. maybe later

    private static function transform($xmlFile, $xslAction = 'display', $xslFile = null)
    {
        $xar = xar::getServicesClass();
        if (!isset($xmlFile)) {
            throw new BadParameterException($xar->mls()->translate('No file to transform!'));
        }

        // Get the database type from the connection
        $databaseType = $xar->db()->getType();
        switch ($databaseType) {
            case 'sqlite3':
            case 'pdosqlite':
                self::$typesObject = new SQLiteTypes();
                $databaseType = 'sqlite3';
                break;
            case 'mysqli':
            case 'pdomysqli':
                self::$typesObject = new MySQLTypes();
                $databaseType = 'mysqli';
                break;
            case 'pgsql':
            case 'pdopgsql':
                self::$typesObject = new PgSQLTypes();
                $databaseType = 'pgsql';
                break;
            default:
                throw new Exception($xar->mls()->translate("Unknown database type: '#(1)'", $databaseType));
        }

        if (!isset($xslFile)) {
            $xslFile = sys::lib() . 'xaraya/tableddl/xml2ddl-' . $databaseType . '.xsl';
        }
        if (!file_exists($xslFile)) {
            $msg = $xar->mls()->translate('The file #(1) was not found', $xslFile);
            throw new BadParameterException(null, $msg);
        }
        $xslProc = new XarayaXSLProcessor($xslFile);
        $xslProc->setParameter('', 'action', $xslAction);
        $xslProc->setParameter('', 'tableprefix', $xar->db()->getPrefix());
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
        $code = (int) CreoleTypes::getCreoleCode(strtoupper($creoleType));
        if (null == $code) {
            xarCore::exit(xar::mls()->translate("Unknown Creole type: '#(1)'", $creoleType));
            return;
        }
        if (null == $type = strtoupper(self::$typesObject::getNativeType($code))) {
            xarCore::exit(xar::mls()->translate("Unknown Creole type: '#(1)'", $creoleType));
            return;
        }
        return $type;
    }

    public static function createTable($tablefile, $module)
    {
        if (empty($module)) {
            throw new BadParameterException('Missing a module name to create for');
        }
        if (empty($tablefile)) {
            throw new BadParameterException('Missing a XML file to create from');
        }

        $xmlfile = sys::code() . 'modules/' . $module . '/xardata/' . $tablefile . '.xml';
        if (!file_exists($xmlfile)) {
            $msg = xar::mls()->translate('Could not find the file #(1) to create tables from', $xmlfile);
            throw new BadParameterException(null, $msg);
        }

        // Create a query string for table creation from the XML schema passed
        $sqlCode = self::transform($xmlfile, 'create');
        // Run the query code to add variable values (there aren't any) and execute any PHP snippets inserted by the transform
        $sqlCode = xar::tpl()->string($sqlCode, []);
        // Turn the query string into an array of queries
        $queries = explode(';', $sqlCode);
        // The last element is empty: remove it
        array_pop($queries);

        $log = xar::log();
        // Execute each of the queries
        $dbconn = xar::db()->getConn();
        foreach ($queries as $q) {
            $log->info('Executing SQL: ' . $q);
            $dbconn->Execute($q);
        }
        return true;
    }
}
