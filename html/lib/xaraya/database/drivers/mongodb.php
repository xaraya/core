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

namespace Xaraya\Database\Drivers;

/**
 * Provide an external database connection via MongoDB PHP Library
 * @uses \sys::autoload()
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
            throw new \Exception("Please use sys::autoload() in Xaraya, and install MongoDB PHP Library:\n$ composer require mongodb/mongodb\n");
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

    /**
     * Summary of getDriverType
     * @param mixed $dbconn
     * @return string
     */
    public static function getDriverType($dbconn)
    {
        /** @var \MongoDB\Database $dbconn */
        return 'MongoDB';
    }

    /**
     * Summary of listTableNames
     * @param mixed $dbconn
     * @return array<string>
     */
    public static function listTableNames($dbconn)
    {
        /** @var \MongoDB\Database $dbconn */
        $collections = $dbconn->listCollectionNames();
        $result = [];
        foreach ($collections as $name) {
            $result[] = $name;
        }
        sort($result);
        return $result;
    }

    /**
     * Summary of listTableColumns
     * @param mixed $dbconn
     * @param string $tablename
     * @return array<string, mixed>
     */
    public static function listTableColumns($dbconn, $tablename)
    {
        /** @var \MongoDB\Database $dbconn */
        // @todo use document schema?
        $collection = $dbconn->selectCollection($tablename);
        $document = $collection->findOne();
        //$document = $collection->findOne([], ['sort' => ['_id' => -1]]);
        $result = [];
        if (!empty($document)) {
            $item = $document->getArrayCopy();
            foreach ($item as $key => $value) {
                if (is_object($value)) {
                    $result[$key] = 'mongodb_bson (' . get_class($value) . ')';
                } elseif (is_string($value) && strlen($value) > 200) {
                    $result[$key] = 'text';
                } else {
                    $result[$key] = gettype($value);
                }
            }
            // use custom datatype for _id here
            if (is_object($item['_id']) || (is_string($item['_id']) && strlen($item['_id']))) {
                $result['_id'] = 'documentid';
            } else {
                $result['_id'] = 'itemid';
            }
        } else {
            // use custom datatype for _id here
            $result['_id'] = 'documentid';
            $result['document'] = 'json';
        }
        return $result;
    }
}
