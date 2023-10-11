<?php
/**
 * Entrypoint for experimenting with virtual objects
 *
 * Needs the following setting in /etc/php[8.x]/cli/php.ini to enable acp(u) for cli:
 * apc.enable_cli=1
 */
require dirname(__DIR__, 3).'/vendor/autoload.php';

// initialize bootstrap
sys::init();
// initialize caching
xarCache::init();

// initialize database for itemid - if not already loaded
//xarDatabase::init();
// for hook calls - if not already loaded
//xarMod::init();

function get_descriptor()
{
    $offline = true;
    $descriptor = new VirtualObjectDescriptor(['name' => 'something'], $offline);
    $descriptor->addProperty(['name' => 'id', 'type' => 'itemid']);
    $descriptor->addProperty(['name' => 'key', 'type' => 'textbox']);
    $descriptor->addProperty(['name' => 'val', 'type' => 'textbox']);
    return $descriptor;
}

function test_create_items()
{
    $descriptor = get_descriptor();
    $something = new DataObject($descriptor);

    $itemid = $something->createItem(['id' => 1, 'key' => 'yes', 'val' => 'OK']);
    echo "Item $itemid\n";
    $itemid = $something->createItem(['id' => 2, 'key' => 'no', 'val' => 'Not OK']);
    echo "Item $itemid\n";
}

function test_update_item()
{
    $descriptor = get_descriptor();
    $something = new DataObject($descriptor);

    $itemid = $something->getItem(['itemid' => 2]);
    var_dump($something->getFieldValues());
    $itemid = $something->updateItem(['val' => 'Maybe OK']);
    var_dump($something->getFieldValues());
}

function test_get_items()
{
    $descriptor = get_descriptor();
    $something = new DataObjectList($descriptor);

    $itemids = $something->datastore->listItemIds();
    $items = $something->getItems(['itemids' => $itemids]);
    var_dump($items);
}

function test_delete_item()
{
    $descriptor = get_descriptor();
    $something = new DataObject($descriptor);

    // @checkme avoid last stand protection in deleteItem()
    $something->objectid = time();
    // @checkme avoid exceptions in xarEvents::notify
    /**
    $something->itemtype = 3;
    xarCoreCache::loadCached('Events.Subjects', '3');
    xarCoreCache::loadCached('Hooks.Observers', 'dynamicdata.3');
    xarCoreCache::loadCached('Events.Subjects', '1');
    xarCoreCache::loadCached('Events.Observers', '2');
    xarCoreCache::loadCached('Mod.BaseInfos');
    xarCoreCache::loadCached('Mod.Infos');
     */
    $itemid = $something->deleteItem(['itemid' => 2]);
    echo "Item $itemid\n";
}

test_create_items();
test_update_item();
test_get_items();
test_delete_item();
test_get_items();
