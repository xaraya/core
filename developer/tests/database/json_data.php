<?php
/**
 * Entrypoint for experimenting with JSON data type in Doctrine DBAL or MongoDB
 *
 * The idea was to see if we could replace xar_dynamic_data with xar_dynamic_json
 * at some point in the future, i.e. switch to a JSON document-style way of working
 * instead of keeping each property value of (objectid + itemid) in separate rows
 *
 * Support for JSON data type in Doctrine DBAL isn't as transparent as I'd hoped yet,
 * but using an external MongoDB seems like an interesting option - to be continued
 */
require dirname(__DIR__, 3).'/vendor/autoload.php';

use Xaraya\Database\ExternalDatabase;

// initialize bootstrap
sys::init();

require __DIR__ . '/dbal_config.php';

/**
 * Create JSON table in MySQL database
 * @param mixed $conn
 * @param mixed $name
 * @return void
 */
function create_json_table($conn, $name = 'xar_dynamic_json')
{
    $sm = $conn->createSchemaManager();
    $platform = $conn->getDatabasePlatform();
    try {
        $table = $sm->introspectTable($name);
        //$sql = $platform->getCreateTableSQL($table, $platform::CREATE_INDEXES | $platform::CREATE_FOREIGNKEYS);
        $sql = $platform->getCreateTableSQL($table);
        print_r($sql);
    } catch (\Doctrine\DBAL\Schema\Exception\TableDoesNotExist $e) {
        $schema = new \Doctrine\DBAL\Schema\Schema();
        $myTable = $schema->createTable($name);
        $myTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
        $myTable->addColumn("object_id", "integer", array("unsigned" => true));
        $myTable->addColumn("item_id", "integer", array("unsigned" => true));
        $myTable->addColumn("data", "json");
        $myTable->setPrimaryKey(array("id"));
        $myTable->addUniqueIndex(array("object_id", "item_id"), 'i_' . $name . '_item');
        $sql = $platform->getCreateTableSQL($myTable);
        print_r($sql);
        $sm->createTable($myTable);
    }
}

/**
 * Add index to JSON table in MySQL database
 * @param mixed $conn
 * @param mixed $name
 * @return void
 */
function add_json_index($conn, $name = 'xar_dynamic_json')
{
    $sm = $conn->createSchemaManager();
    $fromSchema = $sm->introspectSchema();
    $toSchema = clone $fromSchema;
    $table = $toSchema->getTable($name);
    $table->dropIndex('i_' . $name . '_item');
    $table->addUniqueIndex(array("object_id", "item_id"), 'i_' . $name . '_item');

    $platform = $conn->getDatabasePlatform();
    $sql = $fromSchema->getMigrateToSql($toSchema, $platform);
    print_r($sql);
    //foreach ((array) $sql as $query) {
    //    $conn->executeStatement($query);
    //}
}

/**
 * Test queries on JSON table in MySQL - not very convincing
 * @param mixed $conn
 * @param array<mixed> $items
 * @param bool $refresh
 * @return void
 */
function test_json_table($conn, $items = [], $refresh = false)
{
    /**
    if ($refresh) {
        foreach ($items as $itemid => $item) {
            $conn->update("xar_dynamic_json", array("data" => json_encode($item)), array("object_id" => $object["id"], "item_id" => $itemid));
        }
    } else {
        foreach ($items as $itemid => $item) {
            $conn->insert("xar_dynamic_json", array("object_id" => $object["id"], "item_id" => $itemid, "data" => json_encode($item)));
        }
    }
     */
    // very different syntax depending on the database type :-(
    // select * from xar_dynamic_json where object_id=4 order by json_value(data, '$.name');
    // select * from xar_dynamic_json where object_id=4 and json_value(data, '$.name')="Baby";
    // select * from xar_dynamic_json where object_id=4 and json_extract(data, '$.name')="Baby";
    // select * from xar_dynamic_json where object_id=4 and json_contains(data, "Baby", '$.name')=1; // doesn't work
    // select object_id, json_keys(data), count(*) from xar_dynamic_json group by object_id;
    $stats = $conn->fetchAllAssociative("select object_id, json_keys(data), count(*) from xar_dynamic_json group by object_id");
    print_r($stats);
    $test = $conn->fetchAllAssociative("select * from xar_dynamic_json where object_id=4 order by json_value(data, '$.name')");

    print_r($test);
    // https://mariadb.com/resources/blog/json-with-mariadb-10-2/
    //$conn->update("xar_dynamic_json", array("data" => "JSON_REPLACE(data, '$.name', 'strong')"), array("object_id" => 4, "item_id" => 11));
    //$conn->update("xar_dynamic_json", array("data" => json_encode(array("id" => 11, "name" => "strong", "age" => 0, "location" => "lost"))), array("object_id" => 4, "item_id" => 11));
}

/**
 * Copy Xaraya DD object items as collection documents in MongoDB
 * @param string $dbName Xaraya database name
 * @param string $dbConnIndex
 * @param MongoDB\Client $client
 * @param bool $drop default false
 * @return void
 */
function copy_dynamicdata_objects($dbName, $dbConnIndex, $client, $drop = false)
{
    if ($drop) {
        $client->dropDatabase($dbName);
    }
    /** @var Doctrine\DBAL\Connection $conn */
    $conn = ExternalDatabase::getConn($dbConnIndex);
    $sql = "SELECT * FROM xar_dynamic_objects WHERE datastore = ?";
    /** @var Doctrine\DBAL\Statement $stmt */
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, 'dynamicdata');
    $resultSet = $stmt->executeQuery();
    $objects = $resultSet->fetchAllAssociative();
    //print_r($objects);
    $sql2 = "SELECT * FROM xar_dynamic_properties WHERE object_id = ?";
    $stmt2 = $conn->prepare($sql2);
    $sql3 = "SELECT * FROM xar_dynamic_data WHERE property_id IN (?)";
    //$stmt3 = $conn->prepare($sql3);
    foreach ($objects as $object) {
        echo $object["id"] . ": " . $object["name"] . "\n";
        $stmt2->bindValue(1, $object["id"]);
        $resultSet2 = $stmt2->executeQuery();
        $properties = $resultSet2->fetchAllAssociative();
        //print_r($properties);
        $prop_names = array();
        $prop_types = array();
        foreach ($properties as $property) {
            $prop_names[$property["id"]] = $property["name"];
            $prop_types[$property["id"]] = $property["type"];
        }
        $prop_ids = array_keys($prop_names);
        //$stmt3->bindValue(1, $prop_ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        //$stmt3->execute();
        //The parameter list support only works with Doctrine\DBAL\Connection::executeQuery() and Doctrine\DBAL\Connection::executeStatement(), NOT with the binding methods of a prepared statement.
        $resultSet3 = $conn->executeQuery($sql3, array($prop_ids), array(\Doctrine\DBAL\ArrayParameterType::INTEGER));
        $data = $resultSet3->fetchAllAssociative();
        //print_r($data);
        $items = array();
        foreach ($data as $field) {
            if (!array_key_exists($field["item_id"], $items)) {
                $items[$field["item_id"]] = array();
                $items[$field["item_id"]]["_id"] = (int) $field["item_id"];
            }
            if (in_array($prop_types[$field["property_id"]], [21, 15, 18281])) {
                // itemid, integerbox, deferitem
                $items[$field["item_id"]][$prop_names[$field["property_id"]]] = (int) $field["value"];
            } elseif (in_array($prop_types[$field["property_id"]], [7, 24, 507, 20]) && is_numeric($field["value"])) {
                // user, object, objectref, itemtype (if numeric)
                $items[$field["item_id"]][$prop_names[$field["property_id"]]] = (int) $field["value"];
            } elseif (in_array($prop_types[$field["property_id"]], [8]) && is_numeric($field["value"])) {
                // calendar (if numeric) - pass timestamp in milliseconds
                $items[$field["item_id"]][$prop_names[$field["property_id"]]] = new MongoDB\BSON\UTCDateTime(((float) $field["value"]) * 1000);
            } elseif (in_array($prop_types[$field["property_id"]], [14])) {
                // checkbox
                $items[$field["item_id"]][$prop_names[$field["property_id"]]] = (bool) $field["value"];
            } elseif (in_array($prop_types[$field["property_id"]], [17])) {
                // floatbox
                $items[$field["item_id"]][$prop_names[$field["property_id"]]] = (float) $field["value"];
            } elseif (in_array($prop_types[$field["property_id"]], [18282, 18283])) {
                // deferlist, defermany (if not empty)
                $value = json_decode($field["value"], true, 512, JSON_THROW_ON_ERROR);
                $items[$field["item_id"]][$prop_names[$field["property_id"]]] = array_filter($value);
            } else {
                $items[$field["item_id"]][$prop_names[$field["property_id"]]] = $field["value"];
            }
        }
        //echo var_export($items, true) . "\n";
        $collName = "dd_" . $object["name"];
        $collection = $client->selectCollection($dbName, $collName);
        $result = $collection->insertMany(array_values($items));
        printf("Inserted %d document(s)\n", $result->getInsertedCount());
    }
}

/**
 * Copy external database tables as collections in MongoDB
 * @param string $dbName Calibre database name
 * @param string $dbConnIndex
 * @param MongoDB\Client $client
 * @param bool $drop default true
 * @return void
 */
function copy_external_database($dbName, $dbConnIndex, $client, $drop = true)
{
    if ($drop) {
        $client->dropDatabase($dbName);
    }
    $tables = ExternalDatabase::listTableNames($dbConnIndex);
    /** @var PDO $conn */
    $conn = ExternalDatabase::getConn($dbConnIndex);
    //print_r($tables);
    foreach ($tables as $table) {
        $columns = ExternalDatabase::listTableColumns($dbConnIndex, $table);
        echo "Table $table:\n";
        //print_r($columns);
        $primary = '';
        foreach ($columns as $name => $type) {
            if ($type == 'itemid') {
                $primary = $name;
                break;
            }
        }
        // not very elegant way of doing this, but we don't really care
        $sql = 'SELECT * FROM ' . $table;
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        printf("Selected %d items(s)\n", count($items));
        if (empty($items)) {
            continue;
        }
        if (!empty($primary)) {
            foreach (array_keys($items) as $key) {
                $items[$key]['_id'] = $items[$key][$primary];
            }
        }
        $collection = $client->selectCollection($dbName, $table);
        $result = $collection->insertMany(array_values($items));
        printf("Inserted %d document(s)\n", $result->getInsertedCount());
    }
}

/**
 * Copy Xaraya database tables as collections in MongoDB
 * @param string $dbName Xaraya database name
 * @param string $dbConnIndex
 * @param MongoDB\Client $client
 * @param bool $drop default true
 * @return void
 */
function copy_xaraya_database($dbName, $dbConnIndex, $client, $drop = true)
{
    if ($drop) {
        $client->dropDatabase($dbName);
    }
    $tables = ExternalDatabase::listTableNames($dbConnIndex);
    /** @var Doctrine\DBAL\Connection $conn */
    $conn = ExternalDatabase::getConn($dbConnIndex);
    //print_r($tables);
    $todo = [];
    foreach ($tables as $table) {
        $columns = ExternalDatabase::listTableColumns($dbConnIndex, $table);
        //print_r($columns);
        $primary = '';
        foreach ($columns as $name => $type) {
            if ($type == 'itemid') {
                $primary = $name;
                break;
            }
        }
        //echo "Table $table - Primary $primary\n";
        $todo[$table] = $primary;
    }
    foreach ($todo as $table => $primary) {
        // not very elegant way of doing this, but we don't really care
        $sql = "SELECT * from $table";
        $items = $conn->fetchAllAssociative($sql);
        echo "Table $table:\n";
        printf("Selected %d items(s)\n", count($items));
        if (empty($items)) {
            continue;
        }
        // unserialize text values here?
        foreach (array_keys($items) as $key) {
            foreach (array_keys($items[$key]) as $field) {
                if (is_string($items[$key][$field]) && str_starts_with($items[$key][$field], 'a:')) {
                    // clean-up of some messed-up default content
                    if (str_contains($items[$key][$field], '&amp;quot;')) {
                        $items[$key][$field] = str_replace('&amp;quot;', '"', $items[$key][$field]);
                    }
                    try {
                        $value = unserialize($items[$key][$field]);
                        if (is_array($value)) {
                            // clean-up of some messed-up default content
                            if (count($value) > 0 && array_keys($value)[0] === 0) {
                                $value[0] = array_filter($value[0]);
                            }
                            $value = array_filter($value);
                        }
                        $items[$key][$field] = $value;
                    } catch (Throwable $e) {
                    }
                }
            }
        }
        if (!empty($primary)) {
            foreach (array_keys($items) as $key) {
                $items[$key]['_id'] = $items[$key][$primary];
            }
        }
        $collection = $client->selectCollection($dbName, $table);
        try {
            $result = $collection->insertMany(array_values($items));
            printf("Inserted %d document(s)\n", $result->getInsertedCount());
        } catch (Throwable $e) {
            echo json_encode($items, JSON_PRETTY_PRINT) . "\n";
            echo $e->getMessage();
        }
    }
}

/**
$dbName = 'Calibre';
$dbConnArgs = [
    'external' => 'pdo',
    'databaseType' => 'sqlite3',
    'databaseName' => dirname(__DIR__, 3).'/html/code/modules/library/xardata/metadata.db',
];
$dbConnIndex = ExternalDatabase::checkDbConnection(null, $dbConnArgs);
$mongodb = new MongoDB\Client();
copy_external_database($dbName, $dbConnIndex, $mongodb);
 */

// use dbal driver here to get correct primary keys + return value types
$dbName = 'Xaraya';
$dbConnArgs = get_xaraya_params();
$dbConnArgs['external'] = 'dbal';
$dbConnIndex = ExternalDatabase::checkDbConnection(null, $dbConnArgs);
$mongodb = new MongoDB\Client();
copy_xaraya_database($dbName, $dbConnIndex, $mongodb);

copy_dynamicdata_objects($dbName, $dbConnIndex, $mongodb);
