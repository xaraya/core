<?php
/**
 * Provide an external database connection via PHP PDO
 *
 * @package core/database
 * @subpackage database
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Database;

/**
 * Provide an external database connection via PHP PDO
 */
class PdoDriver
{
    /**
     * Summary of getConnection
     * @param array<string, mixed> $dsn
     * @param mixed $flags
     * @return \PDO
     */
    public static function getConnection($dsn, $flags = [])
    {
        [$dsn, $username, $password, $options] = static::mapDSN($dsn, $flags);
        return new \PDO($dsn, $username, $password, $options);
    }

    /**
     * Summary of mapDSN
     * @param array<string, mixed> $dsn
     * @param mixed $flags
     * @return array<mixed>
     */
    public static function mapDSN($dsn, $flags = [])
    {
        $username = $dsn['userName'] ?? null;
        $password = $dsn['password'] ?? null;
        $options = $flags;
        // check if $dsn already contain PDO-compatible parameters
        if (!empty($dsn['dsnstring'])) {
            // @todo check username for other variants if needed
            $dsnstring = $dsn['dsnstring'];
            return [$dsnstring, $username, $password, $options];
        }
        // map Xaraya connection arguments to PDO-compatible parameters
        $parts = [];
        if (!empty($dsn['databaseHost'])) {
            if (str_starts_with($dsn['databaseHost'], '/') && str_contains($dsn['databaseType'], 'mysql')) {
                $parts[] = 'unix_socket=' . $dsn['databaseHost'];
            } else {
                $parts[] = 'host=' . $dsn['databaseHost'];
            }
        }
        if (!empty($dsn['databasePort'])) {
            $parts[] = 'port=' . $dsn['databasePort'];
        }
        if (!empty($dsn['databaseName'])) {
            if (empty($dsn['databaseHost']) && str_contains($dsn['databaseType'], 'sqlite')) {
                // DSN string is sqlite:/home/xaraya-core/html/var/sqlite/xaraya.db
                $parts[] = $dsn['databaseName'];
            } else {
                $parts[] = 'dbname=' . $dsn['databaseName'];
            }
        }
        //if (!empty($dsn['userName'])) {
        //    $parts[] = 'user=' . $dsn['userName'];
        //}
        //if (!empty($dsn['password'])) {
        //    $parts[] = 'password=' . $dsn['password'];
        //}
        if (!empty($dsn['databaseCharset'])) {
            $parts[] = 'charset=' . $dsn['databaseCharset'];
        }
        // we want to get an exception if databaseType is not defined
        $driver = static::mapDriver($dsn['databaseType']);
        $dsnstring = $driver . ':' . implode(';', $parts);
        //echo $dsnstring . PHP_EOL;
        return [$dsnstring, $username, $password, $options];
    }

    /**
     * Summary of mapDriver
     * @param string $dbType
     * @return string
     */
    public static function mapDriver($dbType)
    {
        switch ($dbType) {
            case 'mysqli':
            case 'pdo_mysql':
                return 'mysql';
            case 'sqlite3':
            case 'pdo_sqlite':
                return 'sqlite';
            default:
                return $dbType;
        }
    }
}
