<?php

/**
 * Entrypoint for experimenting with virtual objects
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Xaraya\Services\xar;

// initialize bootstrap
sys::init();
// initialize caching
xar::cache()->init();

// initialize database for itemid - if not already loaded
//xar::db()->init();
// for hook calls - if not already loaded
//xar::mod()->init();

// see lib/xaraya/database.php
function get_xaraya_config()
{
    // Decode encoded DB parameters
    // These need to be there
    $userName = xar::sysConfig()->getVar('DB.UserName');
    $password = xar::sysConfig()->getVar('DB.Password');
    $persistent = null;
    try {
        $persistent = xar::sysConfig()->getVar('DB.Persistent');
    } catch (VariableNotFoundException $e) {
        $persistent = null;
    }
    try {
        if (xar::sysConfig()->getVar('DB.Encoded') == '1') {
            $userName = base64_decode($userName);
            $password  = base64_decode($password);
        }
    } catch (VariableNotFoundException) {
        // doesnt matter, we assume not encoded
    }

    // Hive off the port if there is one added as part of the host
    $host = xar::sysConfig()->getVar('DB.Host');
    $host_parts = explode(':', $host);
    $host = $host_parts[0];
    $port = $host_parts[1] ?? '';

    // Optionals dealt with, do the rest inline
    $systemArgs = ['userName'        => $userName,
        'password'        => $password,
        'databaseHost'    => $host,
        'databasePort'    => $port,
        'databaseType'    => xar::sysConfig()->getVar('DB.Type'),
        'databaseName'    => xar::sysConfig()->getVar('DB.Name'),
        'databaseCharset' => xar::sysConfig()->getVar('DB.Charset'),
        'persistent'      => $persistent,
        'prefix'          => xar::sysConfig()->getVar('DB.TablePrefix')];
    return $systemArgs;
}

function get_descriptor($external = null)
{
    $dbConnArgs = get_xaraya_config();
    if (!empty($external)) {
        $dbConnArgs['external'] = $external;
    }
    $offline = true;
    $descriptor = new TableObjectDescriptor(['table' => 'xar_cache_data', 'dbConnArgs' => $dbConnArgs], $offline);
    return $descriptor;
}

function get_objectitem($external = null)
{
    $descriptor = get_descriptor();
    $objectitem = new DataObject($descriptor);
    return $objectitem;
}

function get_objectlist($external = null)
{
    $descriptor = get_descriptor();
    $objectlist = new DataObjectList($descriptor);
    return $objectlist;
}

function test_create_item($external = null)
{
    $objectitem = get_objectitem($external);
    echo "Datastore: " . $objectitem->datastore::class . "\n";
    $data = [
        'type' => 'test',
        'cache_key' => 'test',
        'code' => '',
        'time' => time(),
        'size' => 0,
        // converted to true/false, which messes up Doctrine DBAL when stored as tinyint
        'cache_check' => 0,
        'data' => 'hello',
    ];
    $itemid = $objectitem->createItem($data);
    echo "Itemid $itemid\n";
    return $itemid;
}

function test_update_item($itemid, $external = null)
{
    $objectitem = get_objectitem($external);
    echo "Datastore: " . $objectitem->datastore::class . "\n";
    $itemid = $objectitem->getItem(['itemid' => $itemid]);
    $data = [
        'data' => 'goodbye',
        // converted to true/false, which messes up Doctrine DBAL when stored as tinyint
        'cache_check' => 0,
    ];
    $itemid = $objectitem->updateItem($data);
    echo "Itemid $itemid\n";
    return $itemid;
}

function test_delete_item($itemid, $external = null)
{
    $objectitem = get_objectitem($external);
    echo "Datastore: " . $objectitem->datastore::class . "\n";
    // @checkme avoid last stand protection in deleteItem()
    $objectitem->objectid = time();
    $itemid = $objectitem->deleteItem(['itemid' => $itemid]);
    echo "Itemid $itemid\n";
    return $itemid;
}

function test_get_item($itemid, $external = null)
{
    $objectitem = get_objectitem($external);
    echo "Datastore: " . $objectitem->datastore::class . "\n";
    $itemid = $objectitem->getItem(['itemid' => $itemid]);
    $item = $objectitem->getFieldValues();
    return $item;
}

function test_get_items($external = null)
{
    $objectlist = get_objectlist($external);
    echo "Datastore: " . $objectlist->datastore::class . "\n";
    //$items = $objectlist->getItems(['where' => ['type eq "test"'], 'fieldlist' => ['type', 'cache_key', 'time', 'data']]);
    $items = $objectlist->getItems(['where' => ["type = 'test'"], 'fieldlist' => ['type', 'cache_key', 'time', 'data']]);
    foreach ($items as $itemid => $item) {
        echo "$itemid: " . var_export($item, true) . "\n";
    }
    return $items;
}

function test_count_items($external = null)
{
    $objectlist = get_objectlist($external);
    echo "Datastore: " . $objectlist->datastore::class . "\n";
    //$items = $objectlist->getItems(['where' => ['type eq "test"'], 'fieldlist' => ['type', 'cache_key', 'time', 'data']]);
    $numitems = $objectlist->countItems(['where' => ["type = 'test'"]]);
    echo "Count: $numitems\n";
    return $numitems;
}

function test_descriptor($external = null)
{
    $descriptor = get_descriptor();
    echo var_export($descriptor->get('propertyargs'), true);
}

$drivers = ['', 'dbal', 'pdo', 'mongodb'];
foreach ($drivers as $driver) {
    $items = test_get_items($driver);
    $itemids = array_keys($items);
    echo "Itemids: " . implode(", ", $itemids) . "\n";
    $itemid = array_shift($itemids);
    echo "Deleting $itemid\n";
    $itemid = test_delete_item($itemid, $driver);
    echo "Deleted $itemid\n";
    $itemid = test_create_item($driver);
    $item = test_get_item($itemid, $driver);
    echo "Item: " . var_export($item, true) . "\n";
    $itemid = test_update_item($itemid, $driver);
    $item = test_get_item($itemid, $driver);
    echo "Item: " . var_export($item, true) . "\n";
    test_count_items($driver);
}
