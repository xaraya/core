<?php
/**
 * Entrypoint for experimenting with ExternalDatabase and DbalDataStore
 *
 * Note: this assumes you install doctrine/dbal with composer
 * and use composer autoload in the entrypoint, see e.g. db.php
 *
 * $ composer require --dev doctrine/dbal
 * $ head html/db.php
 * <?php
 * ...
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html
 */
require_once dirname(__DIR__, 3).'/vendor/autoload.php';

use Xaraya\Database\ExternalDatabase;
use Xaraya\DataObject\DataStores\DbalDataStore;

// initialize bootstrap
sys::init();

require_once __DIR__ . '/db_config.php';

function check_xaraya_columns()
{
    $params = get_xaraya_params();
    $params['external'] = 'dbal';
    $datastore = new DbalDataStore('test', null, $params);
    check_dbal_columns($datastore);
}

function check_calibre_columns()
{
    $params = get_sqlite_params();
    $params['external'] = 'dbal';
    $datastore = new DbalDataStore('test', null, $params);
    // fix wrong type mapping for old Calibre database
    $platform = $datastore->getConnection()->getDatabasePlatform();
    $platform->registerDoctrineTypeMapping('bool', 'boolean');
    $platform->registerDoctrineTypeMapping('integer non', 'integer');
    $platform->registerDoctrineTypeMapping('text non', 'text');
    //$platform->registerDoctrineTypeMapping('enum', 'string');
    check_dbal_columns($datastore);
}

function check_dbal_columns($datastore)
{
    //$dbInfo = $datastore->getDatabaseInfo();
    //echo var_export($dbInfo->listTables(), true);
    $schemaManager = $datastore->getDatabaseInfo();
    $tables = $schemaManager->listTables();
    $dump = [];
    $types = [];
    foreach ($tables as $table) {
        $name = $table->getName();
        $dump[$name] = [];
        foreach ($table->getColumns() as $column) {
            $info = $column->toArray();
            $info['typename'] = \Doctrine\DBAL\Types\Type::lookupName($info['type']);
            $types[$info['typename']] ??= 0;
            $types[$info['typename']] += 1;
            $info['type'] = get_class($info['type']);
            $dump[$name][] = $info;
        }
    }
    echo json_encode($dump, JSON_PRETTY_PRINT) . "\n";
    echo get_class($datastore->getConnection()->getDriver()) . "\n";
    echo json_encode($types, JSON_PRETTY_PRINT) . "\n";
    //echo implode(', ', $table->getPrimaryKey()->getColumns());
    //$datastore->object = (object) ['properties' => [1,2,3], 'primary' => 'id', 'name' => 'eventsystem'];
    //$item = $datastore->getItem(['itemid' => 1]);
    //var_dump($item);
}

function check_mongodb_types($dbName = null, $collName = null)
{
    $dbName ??= 'test';
    $collName ??= 'stuff';
    $client = new MongoDB\Client();
    $collection = $client->selectCollection($dbName, $collName);

    // find the type of each field in the document
    $result = $collection->findOne();
    echo var_export($result, true);
    $values = $result->getArrayCopy();
    echo var_export($values, true);
    // See https://www.mongodb.com/docs/manual/reference/operator/aggregation/type/#example
    $todo = [];
    foreach (array_keys($values) as $key) {
        //if ($key === '_id') {
        //    continue;
        //}
        $todo[$key] = ['$type' => "$$key"];
    }
    $result = $collection->aggregate([
        ['$project' => $todo]
    ]);
    // @todo count types per field in aggregate
    $count = [];
    foreach ($result as $item) {
        //echo var_export($item, true);
        $values = $item->getArrayCopy();
        //echo var_export($values, true);
        foreach ($values as $key => $type) {
            $count[$key] ??= [];
            $count[$key][$type] ??= 0;
            $count[$key][$type] += 1;
        }
    }
    echo var_export($count, true) . "\n";
}

function check_driver_datatypes($driver)
{
    echo "Driver: $driver\n";
    $current = [];
    $dbConnIndex = 0;
    $dbConnArgs = get_xaraya_config();
    $dbConnArgs['external'] = $driver;
    $dbConnIndex = ExternalDatabase::checkDbConnection($dbConnIndex, $dbConnArgs);
    echo "Connection: $dbConnIndex\n";
    $tables = ExternalDatabase::listTableNames($dbConnIndex);
    foreach ($tables as $table) {
        echo "Table: $table\n";
        $columns = ExternalDatabase::listTableColumns($dbConnIndex, $table);
        foreach ($columns as $name => $datatype) {
            echo "\t$name => $datatype\n";
            $current[$datatype] ??= 0;
            $current[$datatype] += 1;
            $total[$datatype] ??= 0;
            $total[$datatype] += 1;
        }
    }
    echo "Driver: $driver\n";
    echo json_encode($current, JSON_PRETTY_PRINT) . "\n";
    echo "\n";
    return $current;
}

function check_external_types()
{
    $drivers = ['dbal', 'pdo', 'mongodb'];
    $total = [];
    foreach ($drivers as $driver) {
        $current = check_driver_datatypes($driver);
        foreach ($current as $datatype => $count) {
            $total[$datatype] ??= 0;
            $total[$datatype] += $count;
        }
    }
    echo json_encode($total, JSON_PRETTY_PRINT) . "\n";
}

//check_xaraya_columns();
//check_calibre_columns();

//check_mongodb_types();
// different types for module_id (null, int) and value (object, string, array)
//check_mongodb_types('Xaraya', 'xar_module_vars');

check_external_types();
