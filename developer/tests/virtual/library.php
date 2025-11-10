<?php

/**
 * Entrypoint for experimenting with library objects (without Xaraya DB)
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Xaraya\Modules\Library\LibraryObject;
use Xaraya\Modules\Library\LibraryObjectList;
use Xaraya\Modules\Library\UserApi;
use Xaraya\Services\xar;

// initialize bootstrap
sys::init();
// initialize caching
xar::cache()->init();

// *don't* initialize database for Xaraya first here (dbConnIndex = 0)
//xar::db()->init();

function get_descriptor($table, $offline)
{
    $filepath = dirname(__DIR__, 3) . '/html/code/modules/library/xardata/lb_' . $table . '-def.php';
    $args = include $filepath;
    $descriptor = VirtualObjectFactory::getObjectDescriptor($args, $offline);
    return $descriptor;
}

//$offline = true;
$offline = false;

if (!$offline) {
    xar::db()->init();
}

$dirpath = dirname(__DIR__, 3) . '/html/code/modules/library/xardata/';
$count = VirtualObjectFactory::loadDefinitions($dirpath);
echo "Found $count object definitions\n";
VirtualObjectFactory::isOffline($offline);

$table = 'books';
//$descriptor = get_descriptor($table, $offline);

// try out session context class
$session = new \Xaraya\Context\SessionContext();
// set instance in xarSession for setCurrentDatabase()
xar::session()->setInstance($session);
//xar::session()->setSessionClass(\Xaraya\Context\SessionContext::class);
//xar::session()->init();

/** @var UserApi $userapi */
$userapi = xarMod::userapi('library');
// set current database before we get to dbConnArgs - this uses xarSession (not initialized) = $_SESSION
$userapi->setCurrentDatabase('test');
if ($offline or true) {
    // add database before we get to dbConnArgs - this avoids using xarModVars (not initialized)
    $filepath = dirname(__DIR__, 3) . '/html/code/modules/library/xardata/metadata.db';
    //$userapi->addDatabase('test', ['databaseType' => 'sqlite3', 'databaseName' => $filepath], false);
    $userapi->addDatabase('test', ['databaseType' => 'sqlite3', 'databaseName' => $filepath, 'external' => 'dbal'], false);
}

//$booklist = new LibraryObjectList($descriptor);
$booklist = VirtualObjectFactory::getObjectList(['name' => 'lb_' . $table]);

//$args = $booklist->descriptor->getArgs();
//echo var_export($args, true);

$items = $booklist->getItems();
echo var_export($items, true) . "\n";

//echo "\nDB Count: " . xarDB::$count . "\n";
//echo "Connection: " . $booklist->dbConnIndex . "\n";
//$dbName = xarDB::getConn($booklist->dbConnIndex)->getDatabaseInfo()->getName();
//echo "Database: $dbName\n";
echo "Datastore: " . $booklist->datastore::class . "\n";

//$bookitem = new LibraryObject($descriptor);
$bookitem = VirtualObjectFactory::getObject(['name' => 'lb_' . $table]);

//$args = $bookitem->descriptor->getArgs();
//echo var_export($args, true);

$itemid = $bookitem->getItem(['itemid' => 2]);
echo var_export($itemid, true) . "\n";
echo "Datastore: " . $bookitem->datastore::class . "\n";
$values = $bookitem->getFieldValues();
echo var_export($values, true) . "\n";
// fix data object loader to retrieve linked object from external too!?
$data = $bookitem->properties['tags']->getDeferredData();
echo var_export($data['value'], true) . "\n";
