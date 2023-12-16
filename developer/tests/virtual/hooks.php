<?php
/**
 * Entrypoint for experimenting with virtual objects
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Xaraya\DataObject\Generated\Sample;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Xaraya\Bridge\Events\EventObserverBridge;
use Xaraya\Bridge\Events\HookObserverBridge;
use Xaraya\Bridge\Events\TestObserverBridgeSubscriber;
use Xaraya\Structures\Context;

// initialize bootstrap
sys::init();
// initialize caching
xarCache::init();

// initialize database for itemid - if not already loaded
xarDatabase::init();
// for hook calls - if not already loaded
xarMod::init();
// for event system - if not already loaded
xarEvents::init();
// for showOutput
//xarTpl::init();

function test_crud()
{
    $itemid = null;
    //$args = [];
    $args = ['name' => 'Mike', 'age' => 20];
    $sample = new Sample($itemid, $args);
    $context = new Context(['function' => __FUNCTION__, 'requestId' => spl_object_id($sample)]);
    $sample->setContext($context);
    $itemid = $sample->save();
    echo "Create $itemid\n";

    $sample = new Sample($itemid);
    $context = new Context(['function' => __FUNCTION__, 'requestId' => spl_object_id($sample)]);
    $sample->setContext($context);
    $values = $sample->toArray();
    echo "Read " . $values['id'] . ": " . $values['name'] . " " . $values['age'] . "\n";
    $sample->set('age', $sample->get('age') + 1);
    $itemid = $sample->save();
    echo "Update $itemid\n";

    $sample = new Sample($itemid);
    $context = new Context(['function' => __FUNCTION__, 'requestId' => spl_object_id($sample)]);
    $sample->setContext($context);
    $values = $sample->toArray();
    echo "Read " . $values['id'] . ": " . $values['name'] . " " . $values['age'] . "\n";
    $sample->delete();
    echo "Delete $itemid\n";
    $sample = new Sample($itemid);
    $context = new Context(['function' => __FUNCTION__, 'requestId' => spl_object_id($sample)]);
    $sample->setContext($context);
    $values = $sample->toArray();
    echo "Read " . $values['id'] . ": " . $values['name'] . " " . $values['age'] . "\n";
}

function hook_bridge()
{
    echo "Hook bridge setup\n";
    // get the event dispatcher we're going to bridge events to
    $dispatcher = new EventDispatcher();
    // set up the event observer bridge for a few events
    $eventbridge = new EventObserverBridge($dispatcher, ['Event']);
    // set up the hook observer bridge for a few hooks
    $hookbridge = new HookObserverBridge($dispatcher, ['ItemCreate', 'ItemUpdate', 'ItemDelete']);

    // have an event subscriber show interest in a few events and/or hooks
    $eventList = array_keys($eventbridge->getObservedEvents());
    echo "Subscribe to Event calls: " . implode(', ', $eventList) . "\n";
    $hookList = array_keys($hookbridge->getObservedEvents());
    echo "Subscribe to Hook calls: " . implode(', ', $hookList) . "\n";
    $subscriber = new TestObserverBridgeSubscriber($eventList, $hookList);
    // and add it to the event dispatcher
    $dispatcher->addSubscriber($subscriber);
    echo "Hook bridge ready\n";
}

function get_descriptor()
{
    $offline = true;
    $descriptor = new VirtualObjectDescriptor(['name' => 'something'], $offline);
    $descriptor->addProperty(['name' => 'id', 'type' => 'itemid']);
    $descriptor->addProperty(['name' => 'key', 'type' => 'textbox']);
    $descriptor->addProperty(['name' => 'val', 'type' => 'textbox']);
    return $descriptor;
}

function test_crud_virtual()
{
    $descriptor = get_descriptor();
    $something = new DataObject($descriptor);
    $context = new Context(['function' => __FUNCTION__, 'requestId' => spl_object_id($something)]);
    $something->setContext($context);

    $itemid = $something->createItem(['id' => 2, 'key' => 'no', 'val' => 'Not OK']);
    echo "Create Item $itemid\n";
    $itemid = $something->getItem(['itemid' => 2]);
    echo "Get Item $itemid\n";
    $itemid = $something->updateItem(['val' => 'Maybe OK']);
    echo "Update Item $itemid\n";
    var_dump($something->getFieldValues());
    // @checkme avoid last stand protection in deleteItem()
    $something->objectid = time();
    $itemid = $something->deleteItem(['itemid' => 2]);
    echo "Delete Item $itemid\n";
}

hook_bridge();
test_crud();
test_crud_virtual();
