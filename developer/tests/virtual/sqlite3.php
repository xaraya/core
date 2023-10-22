<?php
/**
 * Entrypoint for experimenting with sqlite3 database
 */
require dirname(__DIR__, 3).'/vendor/autoload.php';

use Xaraya\DataObject\Export\PhpExporter;

// initialize bootstrap
sys::init();
// initialize caching
xarCache::init();

// initialize database for Xaraya first here (dbConnIndex = 0)
xarDatabase::init();

function get_dbconn_index($filepath)
{
    $args = [
        'databaseType' => 'sqlite3',
        'databaseName' => $filepath,
    ];
    $conn = xarDB::newConn($args);
    $dbConnIndex = xarDB::getConnIndex();

    $dbinfo = $conn->getDatabaseInfo();
    echo "Connection $dbConnIndex: " . $dbinfo->getName() . "\n";
    //$tables = $dbinfo->getTables();
    //foreach ($tables as $table) {
    //    echo $table->getName() . "\n";
    //}
    return $dbConnIndex;
}

function get_descriptor($table, $dbConnIndex)
{
    $descriptor = new TableObjectDescriptor(['table' => 'books', 'dbConnIndex' => $dbConnIndex]);
    return $descriptor;
}

function test_export($descriptor)
{
    $books = new DataObject($descriptor);

    $exporter = new PhpExporter(0);
    $info = $exporter->addObjectDef('', $books);
    echo $info;
}

function test_get_items($descriptor)
{
    $books = new DataObjectList($descriptor);
    echo "Connection: " . $books->datastore->dbConnIndex . "\n";
    //$books->dataquery->debugflag = true;

    $items = $books->getItems();
    var_dump($items);
}

function test_get_item($descriptor)
{
    $books = new DataObject($descriptor);
    echo "Connection: " . $books->datastore->dbConnIndex . "\n";
    //$books->dataquery->debugflag = true;

    $books->getItem(['itemid' => 2]);
    var_dump($books->getFieldValues());
}

$filepath = __DIR__ . '/metadata.db';
$dbConnIndex = get_dbconn_index($filepath);

$table = 'books';
$descriptor = get_descriptor($table, $dbConnIndex);

//test_export($descriptor);
test_get_items($descriptor);
test_get_item($descriptor);
