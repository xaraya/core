<?php

/**
 * Entrypoint for experimenting with virtual objects
 *
 * Needs the following setting in /etc/php[8.x]/cli/php.ini to enable acp(u) for cli:
 * apc.enable_cli=1
 *
 * Trying out brick/varexporter
 * $ composer require --dev brick/varexporter
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Xaraya\DataObject\DataStores\MongoDBDataStore;
use Xaraya\Context\Context;
use Xaraya\DataObject\Generated\VirtualSample;
use Xaraya\DataObject\Generated\VirtualSampleList;
use Xaraya\Services\xar;
use Brick\VarExporter\VarExporter;

// initialize bootstrap
sys::init();
// initialize caching
xar::cache()->init();
//xarCore::xarInit(xarCore::SYSTEM_MODULES);

function init_online()
{
    // initialize database for itemid - if not already loaded
    xar::db()->init();
    // for hook calls - if not already loaded
    xar::mod()->init();
    xar::events()->init();
}

function init_offline_cache()
{
    xar::mem()->load('Events.Subjects', '3');
    xar::mem()->load('Hooks.Observers', 'dynamicdata.0');
    xar::mem()->load('Events.Subjects', '1');
    xar::mem()->load('Events.Observers', '2');
    xar::mem()->load('Mod.BaseInfos');
    /**
    xar::mem()->load('Mod.Infos');
     */
}

function hooks_callback($info, $context = null)
{
    echo "Hook Event: " . var_export($info, true) . "\n";
    echo "Context: " . var_export($context, true) . "\n";
}

function hooks_register()
{
    xarHooks::registerCallback('ItemCreate', 'hooks_callback');
    xarHooks::registerCallback('ItemUpdate', 'hooks_callback');
    xarHooks::registerCallback('ItemDelete', 'hooks_callback');
}

function save_offline_cache()
{
    xar::mem()->save('Events.Subjects', '3');
    xar::mem()->save('Hooks.Observers', 'dynamicdata.0');
    xar::mem()->save('Events.Subjects', '1');
    xar::mem()->save('Events.Observers', '2');
    xar::mem()->save('Mod.BaseInfos');
    xar::mem()->save('Mod.Infos');
}

function get_cache_descriptor()
{
    $offline = true;
    $descriptor = new VirtualObjectDescriptor(['name' => 'something'], $offline);
    $descriptor->addProperty(['name' => 'id', 'type' => 'itemid']);
    $descriptor->addProperty(['name' => 'key', 'type' => 'textbox']);
    $descriptor->addProperty(['name' => 'val', 'type' => 'textbox']);
    return $descriptor;
}

function get_mongodb_descriptor()
{
    $offline = true;
    $descriptor = new VirtualObjectDescriptor(['name' => 'something', 'config' => ''], $offline);
    $descriptor->addProperty(['name' => 'id', 'type' => 'itemid', 'source' => 'stuff.id']);
    $descriptor->addProperty(['name' => 'key', 'type' => 'textbox', 'source' => 'stuff.key']);
    $descriptor->addProperty(['name' => 'val', 'type' => 'textbox', 'source' => 'stuff.val']);
    $config = [
        //'dbConnIndex' => 1,
        'dbConnArgs' => [
            'external' => 'mongodb',
        ],
    ];
    $config['dbConnArgs'] = json_encode($config['dbConnArgs']);
    $descriptor->set('config', serialize($config));
    $descriptor->set('datastore', 'external');
    return $descriptor;
}

function get_descriptor()
{
    //return get_cache_descriptor();
    return get_mongodb_descriptor();
}

function test_create_items()
{
    $descriptor = get_descriptor();
    $something = new DataObject($descriptor);
    echo get_class($something->datastore) . "\n";
    $context = new Context(['source' => __FUNCTION__, 'requestId' => spl_object_id($something)]);
    $something->setContext($context);

    if ($something->datastore instanceof MongoDBDataStore) {
        $something->datastore->deleteAll('stuff');
        // working with or without pre-defined id
        $itemid = $something->createItem(['id' => null, 'key' => 'yes', 'val' => 'OK']);
        echo "Item $itemid\n";
        $itemid = $something->createItem(['id' => null, 'key' => 'no', 'val' => 'Not OK']);
        echo "Item $itemid\n";
    } else {
        $itemid = $something->createItem(['id' => 1, 'key' => 'yes', 'val' => 'OK']);
        echo "Item $itemid\n";
        $itemid = $something->createItem(['id' => 2, 'key' => 'no', 'val' => 'Not OK']);
        echo "Item $itemid\n";
    }
    return $itemid;
}

function test_update_item($lastid = 2)
{
    $descriptor = get_descriptor();
    $something = new DataObject($descriptor);
    $context = new Context(['source' => __FUNCTION__, 'requestId' => spl_object_id($something)]);
    $something->setContext($context);

    $itemid = $something->getItem(['itemid' => $lastid]);
    var_dump($something->getFieldValues());
    $itemid = $something->updateItem(['val' => 'Maybe OK']);
    var_dump($something->getFieldValues());
}

function test_get_items()
{
    $descriptor = get_descriptor();
    $something = new DataObjectList($descriptor);
    $context = new Context(['source' => __FUNCTION__, 'requestId' => spl_object_id($something)]);
    $something->setContext($context);

    if ($something->datastore instanceof CachingDataStore) {
        $itemids = $something->datastore->listItemIds();
        $items = $something->getItems(['itemids' => $itemids]);
    } else {
        $items = $something->getItems();
    }
    var_dump($items);
}

function test_delete_item($lastid = 2)
{
    $descriptor = get_descriptor();
    $something = new DataObject($descriptor);
    $context = new Context(['source' => __FUNCTION__, 'requestId' => spl_object_id($something)]);
    $something->setContext($context);

    // @checkme avoid last stand protection in deleteItem()
    $something->objectid = time();
    $itemid = $something->deleteItem(['itemid' => $lastid]);
    echo "Item $itemid\n";
}

function test_virtual_sample()
{
    xar::db()->init();
    $context = new Context(['source' => __FUNCTION__]);
    $sample = new VirtualSample(['itemid' => 1], $context);
    echo get_class($sample) . "\n";
    $itemid = $sample->getItem();
    echo "Item: $itemid\n";
    echo "Values: " . var_export($sample->getFieldValues(), true) . "\n";
    echo "Context: " . var_export($sample->getContext(), true) . "\n";
    $samples = new VirtualSampleList([], $context);
    echo get_class($samples) . "\n";
    $items = $samples->getItems();
    echo "Items: " . var_export($items, true) . "\n";
    foreach (array_keys($sample->properties) as $name) {
        $sample->properties[$name]->objectref = null;
        $sample->properties[$name]->descriptor->set('objectref', null);
    }
    $sample->datastore->object = null;
    $sample->setContext(null);
    //var_dump($sample);
    //var_export($sample->datastore);
    //print_r($sample);
    //echo VarExporter::export($sample, VarExporter::ADD_RETURN | VarExporter::ADD_TYPE_HINTS);
}

function test_normal_sample()
{
    xar::db()->init();
    $context = new Context(['source' => __FUNCTION__]);
    $sample = DataObjectFactory::getObject(['name' => 'sample', 'itemid' => 1], $context);
    echo get_class($sample) . "\n";
    $itemid = $sample->getItem();
    echo "Item: $itemid\n";
    echo "Values: " . var_export($sample->getFieldValues(), true) . "\n";
    echo "Context: " . var_export($sample->getContext(), true) . "\n";
    $samples = DataObjectFactory::getObjectList(['name' => 'sample'], $context);
    echo get_class($samples) . "\n";
    $items = $samples->getItems();
    echo "Items: " . var_export($items, true) . "\n";

    $filepath = sys::varpath() . '/cache/variables/sample-export.php';
    DataObjectFactory::unlinkObjectRef($sample);
    //var_dump($sample);
    //var_export($sample->datastore);
    //print_r($sample);
    $content = '<?php
';
    $content .= VarExporter::export($sample, VarExporter::ADD_RETURN | VarExporter::ADD_TYPE_HINTS);
    file_put_contents($filepath, $content);
    /**
    $content = '<?php
$object = ' . var_export($sample, true) . ';
return $object;
';
    $filepath = sys::varpath() . '/cache/variables/sample-exported.php';
    file_put_contents($filepath, $content);
    */

    $filepath = sys::varpath() . '/cache/variables/samplelist-export.php';
    DataObjectFactory::unlinkObjectRef($samples);
    //var_dump($samples);
    //var_export($samples->datastore);
    //print_r($samples);
    $content = '<?php
';
    $content .= VarExporter::export($samples, VarExporter::ADD_RETURN | VarExporter::ADD_TYPE_HINTS);
    file_put_contents($filepath, $content);
    /**
    $content = '<?php
$object = ' . var_export($samples, true) . ';
return $object;
';
    $filepath = sys::varpath() . '/cache/variables/samplelist-exported.php';
    file_put_contents($filepath, $content);
     */
}

/**
//init_online();
init_offline_cache();
hooks_register();
$lastid = test_create_items();
test_update_item($lastid);
test_get_items();
test_delete_item($lastid);
test_get_items();
//save_offline_cache();
 */

//test_virtual_sample();
test_normal_sample();
