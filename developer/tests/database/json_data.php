<?php
/**
 * Entrypoint for experimenting with JSON data type in Doctrine DBAL
 *
 * The idea was to see if we could replace xar_dynamic_data with xar_dynamic_json
 * at some point in the future, i.e. switch to a JSON document-style way of working
 * instead of keeping each property value of (objectid + itemid) in separate rows
 *
 * Currently on hold - support for JSON data type in Doctrine DBAL isn't as transparent as I'd hoped yet
 */
require dirname(__DIR__, 3).'/vendor/autoload.php';

// initialize bootstrap
sys::init();

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

function fill_json_table($conn, $refresh = false)
{
    $sql = "SELECT * FROM xar_dynamic_objects WHERE datastore = ?";
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
        print_r($properties);
        $prop_names = array();
        foreach ($properties as $property) {
            $prop_names[$property["id"]] = $property["name"];
        }
        $prop_ids = array_keys($prop_names);
        //$stmt3->bindValue(1, $prop_ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        //$stmt3->execute();
        //The parameter list support only works with Doctrine\DBAL\Connection::executeQuery() and Doctrine\DBAL\Connection::executeStatement(), NOT with the binding methods of a prepared statement.
        $resultSet3 = $conn->executeQuery($sql3, array($prop_ids), array(\Doctrine\DBAL\ArrayParameterType::INTEGER));
        $data = $resultSet3->fetchAllAssociative();
        print_r($data);
        $items = array();
        foreach ($data as $field) {
            if (!array_key_exists($field["item_id"], $items)) {
                $items[$field["item_id"]] = array();
            }
            $items[$field["item_id"]][$prop_names[$field["property_id"]]] = $field["value"];
        }
        print_r($items);
        if ($refresh) {
            foreach ($items as $itemid => $item) {
                $conn->update("xar_dynamic_json", array("data" => json_encode($item)), array("object_id" => $object["id"], "item_id" => $itemid));
            }
        } else {
            foreach ($items as $itemid => $item) {
                $conn->insert("xar_dynamic_json", array("object_id" => $object["id"], "item_id" => $itemid, "data" => json_encode($item)));
            }
        }
    }
}

function test_json_table($conn)
{
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

//currently on hold
