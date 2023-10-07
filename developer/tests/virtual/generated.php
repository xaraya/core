<?php
/**
 * Entrypoint for experimenting with virtual objects
 */
require dirname(__DIR__, 3).'/vendor/autoload.php';

use Xaraya\DataObject\Generated\Sample;

// initialize bootstrap
sys::init();
// initialize caching
xarCache::init();

// load core cache with property types and configurations
Sample::loadCoreCache();

// initialize database for itemid - if not already loaded
xarDatabase::init();
// @checkme for object URLs - if not already loaded
//xarMod::init();
// for showOutput
//xarTpl::init();
// see CommonBridgeTrait::prepareController()
xarController::$buildUri = function ($module, $type, $func, $extra) {
    $module ??= 'object';
    $type ??= 'sample';
    $func ??= 'view';
    $query = '';
    if (!empty($query)) {
        $query = '?' . http_build_query($extra);
    }
    return "/{$module}/{$type}/{$func}{$query}";
};

const TEST_COUNT = 5000;

function mini_profile($profile, $callable, $itemid = null)
{
    echo "Profile: $profile\n";
    $used = memory_get_usage(true);
    $start = microtime(true);

    $count = call_user_func($callable, $itemid);

    $stop = microtime(true);
    $elapsed = sprintf('%.3f', $stop - $start);
    $memory = sprintf('%.1f', (memory_get_usage(true) - $used) / 1024 / 1024);
    echo "Elapsed: $elapsed sec - Memory: $memory MB - Count: $count\n\n";
}

function test_normal_baseline($itemid = null)
{
    $coll = new ArrayObject();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = DataObjectMaster::getObject(['name' => 'sample']);
        if (!empty($itemid)) {
            $sample->getItem(['itemid' => $itemid]);
        }
        $sample->setFieldValues($args);
        $coll[] = $sample;
    }
    $values = $coll[25]->getFieldValues();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

function test_normal_unserialize($itemid = null)
{
    $sample = DataObjectMaster::getObject(['name' => 'sample']);
    $serialized = serialize($sample);
    $coll = new ArrayObject();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = unserialize($serialized);
        if (!empty($itemid)) {
            $sample->getItem(['itemid' => $itemid]);
        }
        $sample->setFieldValues($args);
        $coll[] = $sample;
    }
    $values = $coll[25]->getFieldValues();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

function test_normal_clone($itemid = null)
{
    echo "Not supported for DataObject()\n";
    return 0;
}

function test_generated_baseline($itemid = null)
{
    $coll = new ArrayObject();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = new Sample($itemid, $args);
        $coll[] = $sample;
    }
    $values = $coll[25]->toArray();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

function test_generated_unserialize($itemid = null)
{
    $args = ['name' => "Mike", 'age' => 20];
    $sample = new Sample($itemid, $args);
    $serialized = serialize($sample);
    $coll = new ArrayObject();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = unserialize($serialized);
        if (!empty($itemid)) {
            $sample->retrieve($itemid);
        }
        foreach ($args as $key => $val) {
            $sample->set($key, $val);
        }
        $coll[] = $sample;
    }
    $values = $coll[25]->toArray();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

function test_generated_clone($itemid = null)
{
    $args = ['name' => "Mike", 'age' => 20];
    $base = new Sample($itemid, $args);
    $coll = new ArrayObject();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = clone $base;
        if (!empty($itemid)) {
            $sample->retrieve($itemid);
        }
        foreach ($args as $key => $val) {
            $sample->set($key, $val);
        }
        $coll[] = $sample;
    }
    $values = $coll[25]->toArray();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

$itemid = null;
//$itemid = 1;
mini_profile("Normal baseline", function ($itemid) { return test_normal_baseline($itemid); }, $itemid);
mini_profile("Normal unserialize", function ($itemid) { return test_normal_unserialize($itemid); }, $itemid);
mini_profile("Normal clone", function ($itemid) { return test_normal_clone($itemid); }, $itemid);
mini_profile("Generated baseline", function ($itemid) { return test_generated_baseline($itemid); }, $itemid);
mini_profile("Generated unserialize", function ($itemid) { return test_generated_unserialize($itemid); }, $itemid);
mini_profile("Generated clone", function ($itemid) { return test_generated_clone($itemid); }, $itemid);

//$args = [];
//$args = ['name' => 'Mike', 'age' => 20];
//$sample = new Sample($itemid, $args);
//var_dump($sample->get('children'));
//var_dump($sample->children->getDeferredData());
//var_dump($sample->children->showOutput());
