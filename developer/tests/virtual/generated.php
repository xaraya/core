<?php
/**
 * Entrypoint for experimenting with virtual objects
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Xaraya\DataObject\Generated\Sample;

// initialize bootstrap
sys::init();
// initialize caching
xarCache::init();

// initialize database for itemid - if not already loaded
xarDatabase::init();
// for hook calls - if not already loaded
//xarMod::init();
// for showOutput
//xarTpl::init();

const TEST_COUNT = 5000;

class LocalProfiler
{
    public static float $total = 0.0;
    public static int $count = 0;
    public static float $now = 0.0;

    public static function clear(): void
    {
        static::$total = 0.0;
        static::$count = 0;
    }

    public static function start(): void
    {
        static::$now = microtime(true);
    }

    public static function stop(): void
    {
        $elapsed = microtime(true) - static::$now;
        static::$total += $elapsed;
        static::$count += 1;
    }

    public static function result(): string
    {
        $result = "Total: " . sprintf('%.3f', static::$total) . " sec - Count: " . sprintf('%d', static::$count);
        if (static::$count > 0) {
            $result .= " - Average: " . sprintf('%.3f', (static::$total * 1000.0 / static::$count)) . " msec\n";
        } else {
            $result .= " - Average: N/A msec\n";
        }
        static::clear();
        return $result;
    }
}

function mini_profile($profile, $callable, $itemid = null)
{
    echo "Profile: $profile\n";
    $used = memory_get_usage(true);
    $start = microtime(true);

    $count = call_user_func($callable, $itemid);

    $stop = microtime(true);
    $elapsed = sprintf('%.3f', $stop - $start);
    $memory = sprintf('%.1f', (memory_get_usage(true) - $used) / 1024 / 1024);
    $average = 'N/A';
    if ($count > 0) {
        $average = round(($stop - $start) * 1000.0 / $count, 3);
    }
    echo "Elapsed: $elapsed sec - Memory: $memory MB - Count: $count - Average: $average msec\n\n";
}

function test_normal_baseline($itemid = null)
{
    $coll = new ArrayObject();
    LocalProfiler::clear();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = DataObjectFactory::getObject(['name' => 'sample']);
        if (!empty($itemid)) {
            $sample->getItem(['itemid' => $itemid]);
        }
        $sample->setFieldValues($args);
        $coll[] = $sample;
    }
    echo LocalProfiler::result();
    $values = $coll[25]->getFieldValues();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

function test_normal_unserialize($itemid = null)
{
    $sample = DataObjectFactory::getObject(['name' => 'sample']);
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
    LocalProfiler::clear();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        Sample::$_object = null;
        Sample::$_descriptor = null;
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = new Sample($itemid, $args);
        $coll[] = $sample;
    }
    echo LocalProfiler::result();
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

function run_profile($itemid = null)
{
    mini_profile("Normal baseline", function ($itemid) { return test_normal_baseline($itemid); }, $itemid);
    mini_profile("Normal unserialize", function ($itemid) { return test_normal_unserialize($itemid); }, $itemid);
    mini_profile("Normal clone", function ($itemid) { return test_normal_clone($itemid); }, $itemid);
    mini_profile("Generated baseline", function ($itemid) { return test_generated_baseline($itemid); }, $itemid);
    mini_profile("Generated unserialize", function ($itemid) { return test_generated_unserialize($itemid); }, $itemid);
    mini_profile("Generated clone", function ($itemid) { return test_generated_clone($itemid); }, $itemid);
}

function test_crud()
{
    $itemid = null;
    //$args = [];
    $args = ['name' => 'Mike', 'age' => 20];
    $sample = new Sample($itemid, $args);
    $itemid = $sample->save();
    echo "Create $itemid\n";

    $sample = new Sample($itemid);
    $values = $sample->toArray();
    echo "Read " . $values['id'] . ": " . $values['name'] . " " . $values['age'] . "\n";
    $sample->set('age', $sample->get('age') + 1);
    $itemid = $sample->save();
    echo "Update $itemid\n";

    $sample = new Sample($itemid);
    $values = $sample->toArray();
    echo "Read " . $values['id'] . ": " . $values['name'] . " " . $values['age'] . "\n";
    $sample->delete();
    echo "Delete $itemid\n";
    $sample = new Sample($itemid);
    $values = $sample->toArray();
    echo "Read " . $values['id'] . ": " . $values['name'] . " " . $values['age'] . "\n";
}

function test_list()
{
    $result = Sample::list();
    foreach ($result as $itemid => $item) {
        echo "Item $itemid\n";
        var_dump($item->toArray());
    }
    $sample = $result[2];
    // we need to refresh here to get the right values in the data object
    $sample->refresh();
    var_dump($sample->partner->getValue());
    $data = $sample->partner->getDeferredData();
    var_dump($data['value']);
    $data = $sample->children->getDeferredData();
    var_dump($data['value']);
}

function test_properties()
{
    $itemid = null;
    $args = ['id' => 4, 'name' => 'Mike', 'age' => 20, 'children' => [5]];
    $sample = new Sample($itemid, $args);
    var_dump($sample->id->getValue());
    var_dump($sample->name->getValue());
    var_dump($sample->get('children'));
    $data = $sample->children->getDeferredData();
    var_dump($data['value']);
    var_dump($sample->children->getDeferredLoader());
    xarTpl::init();
    var_dump($sample->name->showOutput());
}

$itemid = null;
//$itemid = 1;
//run_profile($itemid);

//test_crud();
//test_list();
test_properties();
