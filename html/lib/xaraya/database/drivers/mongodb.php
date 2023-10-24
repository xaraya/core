<?php
/**
 * Provide an external database connection via MongoDB PHP Library
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
 * Provide an external database connection via MongoDB PHP Library
 */
class MongoDBDriver
{
    /**
     * Summary of getConnection
     * @param array<string, mixed> $dsn
     * @param mixed $flags
     * @throws \Exception
     * @return \MongoDB\Database
     */
    public static function getConnection($dsn, $flags)
    {
        if (!class_exists('\\MongoDB\\Client')) {
            throw new \Exception("Please install MongoDB PHP Library:\n$ composer require mongodb/mongodb\n");
        }
        // @todo add mapping for non-localhost configs
        $params = static::mapDSN($dsn, $flags);
        $client = new \MongoDB\Client();
        // use default MongoDB database if not specified
        $params['databaseName'] ??= 'test';
        return $client->selectDatabase($params['databaseName']);
    }

    /**
     * Summary of mapDSN
     * @param array<string, mixed> $dsn
     * @param mixed $flags
     * @return array<mixed>
     */
    public static function mapDSN($dsn, $flags = [])
    {
        // see https://www.mongodb.com/docs/manual/reference/connection-string/
        return $dsn;
    }
}
