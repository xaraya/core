<?php
/**
 * Entrypoint for experimenting with library objects (without Xaraya DB)
 */
require dirname(__DIR__, 3).'/vendor/autoload.php';

use Xaraya\Modules\Library\LibraryObject;
use Xaraya\Modules\Library\LibraryObjectList;
use Xaraya\Modules\Library\UserApi;

// initialize bootstrap
sys::init();
// initialize caching
xarCache::init();

// *don't* initialize database for Xaraya first here (dbConnIndex = 0)
//xarDatabase::init();

function get_descriptor($table)
{
    $filepath = dirname(__DIR__, 3).'/html/code/modules/library/xardata/lb_' . $table . '-def.php';
    if (!is_file($filepath)) {
        die('Unable to find ' . $filepath);
    }
    $args = include $filepath;
    $arrayargs = ['access', 'config', 'sources', 'relations', 'objects', 'category'];
    foreach ($arrayargs as $name) {
        if (!empty($args[$name]) && is_array($args[$name])) {
            $args[$name] = serialize($args[$name]);
        }
    }
    $offline = true;
    $descriptor = new VirtualObjectDescriptor($args, $offline);
    return $descriptor;
}

$table = 'books';
$descriptor = get_descriptor($table);

// set current database before we get to dbConnArgs - this uses xarSession (not initialized) = $_SESSION
UserApi::setCurrentDatabase('test');
// add database before we get to dbConnArgs - this avoids using xarModVars (not initialized)
$filepath = dirname(__DIR__, 3).'/html/code/modules/library/xardata/metadata.db';
UserApi::addDatabase('test', ['databaseType' => 'sqlite3', 'databaseName' => $filepath]);

$booklist = new LibraryObjectList($descriptor);

//$args = $booklist->descriptor->getArgs();
//echo var_export($args, true);

$items = $booklist->getItems();
echo var_export($items, true);
echo "\nDB Count: " . xarDB::$count . "\n";
echo "Connection: " . $booklist->dbConnIndex . "\n";
$dbName = xarDB::getConn($booklist->dbConnIndex)->getDatabaseInfo()->getName();
echo "Database: $dbName\n";
