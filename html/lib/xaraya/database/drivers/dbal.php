<?php
/**
 * Provide an external database connection via Doctrine DBAL
 *
 * @package core/database
 * @subpackage database
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Database\Drivers;

/**
 * Provide an external database connection via Doctrine DBAL
 * @uses \sys::autoload()
 */
class DbalDriver
{
    /**
     * Summary of getConnection
     * @param array<string, mixed> $dsn
     * @param mixed $flags
     * @throws \Exception
     * @return \Doctrine\DBAL\Connection
     */
    public static function getConnection($dsn, $flags)
    {
        if (!class_exists('\\Doctrine\\DBAL\\DriverManager')) {
            throw new \Exception("Please use sys::autoload() in Xaraya, and install Doctrine DBAL:\n$ composer require doctrine/dbal\n");
        }
        $params = static::mapDSN($dsn, $flags);
        return \Doctrine\DBAL\DriverManager::getConnection($params);
    }

    /**
     * Summary of mapDSN
     * @param array<string, mixed> $dsn
     * @param mixed $flags
     * @return array<string, mixed>
     */
    public static function mapDSN($dsn, $flags = [])
    {
        // check if $dsn already contain DBAL-compatible parameters
        if (!empty($dsn['driver']) && (!empty($dsn['dbname']) || !empty($dsn['path']))) {
            return $dsn;
        }
        // map Xaraya connection arguments to DBAL-compatible parameters
        $params = [];
        // we want to get an exception if databaseType is not defined
        $params['driver'] = $dsn['databaseType'];
        if (empty($dsn['databaseHost']) && str_contains($params['driver'], 'sqlite')) {
            $params['path'] = $dsn['databaseName'];
        } else {
            $params['dbname'] = $dsn['databaseName'];
        }
        if (!empty($dsn['databaseHost'])) {
            if (str_starts_with($dsn['databaseHost'], '/') && str_contains($params['driver'], 'mysql')) {
                $params['unix_socket'] = $dsn['databaseHost'];
            } else {
                $params['host'] = $dsn['databaseHost'];
            }
        }
        if (!empty($dsn['databasePort'])) {
            $params['port'] = $dsn['databasePort'];
        }
        if (!empty($dsn['userName'])) {
            $params['user'] = $dsn['userName'];
        }
        if (!empty($dsn['password'])) {
            $params['password'] = $dsn['password'];
        }
        if (!empty($dsn['databaseCharset'])) {
            $params['charset'] = $dsn['databaseCharset'];
        }
        return $params;
    }

    /**
     * Summary of getDriverType
     * @param mixed $dbconn
     * @return string
     */
    public static function getDriverType($dbconn)
    {
        /** @var \Doctrine\DBAL\Connection $dbconn */
        //return 'DBAL TODO';
        return get_class($dbconn->getDriver());
    }

    /**
     * Summary of listTableNames
     * @param mixed $dbconn
     * @return array<string>
     */
    public static function listTableNames($dbconn)
    {
        /** @var \Doctrine\DBAL\Connection $dbconn */
        $sm = $dbconn->createSchemaManager();
        $tables = $sm->listTableNames();
        sort($tables);
        return $tables;
    }

    /**
     * Summary of listTableColumns
     * @param mixed $dbconn
     * @param string $tablename
     * @return array<string, mixed>
     */
    public static function listTableColumns($dbconn, $tablename)
    {
        /** @var \Doctrine\DBAL\Connection $dbconn */
        $sm = $dbconn->createSchemaManager();
        $columns = $sm->listTableColumns($tablename);
        $indexes = $sm->listTableIndexes($tablename);
        $primary = '';
        foreach ($indexes as $index) {
            if ($index->isPrimary() && count($index->getColumns()) == 1) {
                $primary = $index->getColumns()[0];
                break;
            }
        }
        $result = [];
        foreach ($columns as $column) {
            $name = $column->getName();
            $type = $column->getType();
            $typeName = \Doctrine\DBAL\Types\Type::lookupName($type);
            if (!empty($primary) && $primary == $name) {
                $typeName = 'itemid';
            } elseif (empty($primary) && $name == 'id' && $typeName == 'integer') {
                $typeName = 'itemid';
            }
            $result[$name] = $typeName;
        }
        return $result;
    }
}
