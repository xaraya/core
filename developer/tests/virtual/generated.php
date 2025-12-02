<?php

/**
 * Entrypoint for experimenting with virtual objects
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Xaraya\DataObject\Generated\Sample;
use Xaraya\DataObject\Generated\VirtualSample;
//use Brick\VarExporter\VarExporter;
use Xaraya\Services\xar;

if (!class_exists('\Brick\VarExporter\VarExporter')) {
    //return;
}

// initialize bootstrap
sys::init();
// initialize caching
xar::cache()->init();

// initialize database for itemid - if not already loaded
xar::db()->init();
// for hook calls - if not already loaded
//xar::mod()->init();
// for showOutput
//xar::tpl()->init();
//xar::load(xarCore::SYSTEM_MODULES);

const TEST_COUNT = 5000;

/**
 * Local time spent in code loops
 */
class XarayaTimer
{
    public float $total = 0.0;
    public int $count = 0;
    public float $now = 0.0;

    public function __construct(public string $label) {}

    public function start(): void
    {
        $this->now = microtime(true);
    }

    public function stop(): void
    {
        $elapsed = microtime(true) - $this->now;
        $this->total += $elapsed;
        $this->count += 1;
    }

    public function result(): string
    {
        $result = "Total " . $this->label . ": " . sprintf('%.3f', $this->total) . " sec - Count: " . sprintf('%d', $this->count);
        if ($this->count > 0) {
            $result .= " - Average: " . sprintf('%.3f', ($this->total * 1000.0 / $this->count)) . " msec\n";
        } else {
            $result .= " - Average: N/A msec\n";
        }
        return $result;
    }
}

/**
 * Xaraya Profiler for local time spent in code loops
 */
class XarayaProfiler
{
    public const DEFAULT = 'default';
    /** @var array<string, XarayaTimer> */
    public static array $timers;

    public static function clear(string $label = self::DEFAULT): void
    {
        unset(static::$timers[$label]);
    }

    public static function start(string $label = self::DEFAULT): void
    {
        static::getTimer($label)->start();
    }

    public static function stop(string $label = self::DEFAULT): void
    {
        static::getTimer($label)->stop();
    }

    public static function result(string $label = self::DEFAULT): string
    {
        $result = static::getTimer($label)->result();
        static::clear($label);
        return $result;
    }

    protected static function getTimer(string $label = self::DEFAULT): XarayaTimer
    {
        if (!isset(static::$timers[$label])) {
            static::$timers[$label] = new XarayaTimer($label);
        }
        return static::$timers[$label];
    }

    public static function profile($profile, $callable, $args)
    {
        echo "Profile: $profile\n";
        $used = memory_get_usage(true);
        $start = microtime(true);

        $count = call_user_func_array($callable, $args);

        $stop = microtime(true);
        $elapsed = sprintf('%.3f', $stop - $start);
        $memory = sprintf('%.1f', (memory_get_usage(true) - $used) / 1024 / 1024);
        $average = 'N/A';
        if ($count > 0) {
            $average = round(($stop - $start) * 1000.0 / $count, 3);
        }
        echo "Elapsed: $elapsed sec - Memory: $memory MB - Count: $count - Average: $average msec\n\n";
    }
}

function mini_profile($profile, $callable, $itemid = null)
{
    XarayaProfiler::profile($profile, $callable, [$itemid]);
}

function test_normal_baseline($itemid = null)
{
    $coll = new ArrayObject();
    $xar = xar::getServicesClass();
    XarayaProfiler::clear();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = DataObjectFactory::getObject(['name' => 'sample'], null, $xar);
        if (!empty($itemid)) {
            $sample->getItem(['itemid' => $itemid]);
        }
        $sample->setFieldValues($args);
        $coll[] = $sample;
    }
    echo XarayaProfiler::result();
    $values = $coll[25]->getFieldValues();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

function test_normal_unserialize($itemid = null)
{
    $xar = xar::getServicesClass();
    $sample = DataObjectFactory::getObject(['name' => 'sample'], null, $xar);
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
    $xar = xar::getServicesClass();
    $base = DataObjectFactory::getObject(['name' => 'sample'], null, $xar);
    DataObjectFactory::unlinkObjectRef($base);
    $coll = new ArrayObject();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = clone $base;
        DataObjectFactory::relinkObjectRef($sample);
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

function test_normal_export($itemid = null)
{
    $filepath = sys::varpath() . '/cache/variables/sample-export.php';
    if (!file_exists($filepath)) {
        $sample = DataObjectFactory::getObject(['name' => 'sample']);
        DataObjectFactory::unlinkObjectRef($sample);
        $content = '<?php
';
        $content .= VarExporter::export($sample, VarExporter::ADD_RETURN | VarExporter::ADD_TYPE_HINTS);
        file_put_contents($filepath, $content);
    }
    $coll = new ArrayObject();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = require $filepath;
        DataObjectFactory::relinkObjectRef($sample);
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

function test_generated_baseline($itemid = null)
{
    $xar = xar::getServicesClass();
    $coll = new ArrayObject();
    XarayaProfiler::clear();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        Sample::$_object = null;
        Sample::$_descriptor = null;
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = new Sample($itemid, $args, $xar);
        $coll[] = $sample;
    }
    echo XarayaProfiler::result();
    $values = $coll[25]->toArray();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

function test_generated_unserialize($itemid = null)
{
    $xar = xar::getServicesClass();
    $args = ['name' => "Mike", 'age' => 20];
    $sample = new Sample($itemid, $args, $xar);
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
    $xar = xar::getServicesClass();
    $args = ['name' => "Mike", 'age' => 20];
    $base = new Sample($itemid, $args, $xar);
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

function test_virtual_baseline($itemid = null)
{
    $xar = xar::getServicesClass();
    $coll = new ArrayObject();
    XarayaProfiler::clear();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = new VirtualSample([], null, $xar);
        if (!empty($itemid)) {
            $sample->getItem(['itemid' => $itemid]);
        }
        $sample->setFieldValues($args);
        $coll[] = $sample;
    }
    echo XarayaProfiler::result();
    $values = $coll[25]->getFieldValues();
    echo "Check: " . $values['name'] . " " . $values['age'] . "\n";
    return count($coll);
}

function test_virtual_unserialize($itemid = null)
{
    $xar = xar::getServicesClass();
    $sample = new VirtualSample([], null, $xar);
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

function test_virtual_clone($itemid = null)
{
    $xar = xar::getServicesClass();
    $base = new VirtualSample([], null, $xar);
    DataObjectFactory::unlinkObjectRef($base);
    $coll = new ArrayObject();
    for ($i = 0; $i < TEST_COUNT; $i++) {
        $args = ['name' => "Mike $i", 'age' => 20 + $i];
        $sample = clone $base;
        DataObjectFactory::relinkObjectRef($sample);
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

function run_profile($itemid = null)
{
    mini_profile("Normal baseline", function ($itemid) { return test_normal_baseline($itemid); }, $itemid);
    mini_profile("Normal unserialize", function ($itemid) { return test_normal_unserialize($itemid); }, $itemid);
    mini_profile("Normal clone", function ($itemid) { return test_normal_clone($itemid); }, $itemid);
    //mini_profile("Normal export", function ($itemid) { return test_normal_export($itemid); }, $itemid);
    mini_profile("Generated baseline", function ($itemid) { return test_generated_baseline($itemid); }, $itemid);
    mini_profile("Generated unserialize", function ($itemid) { return test_generated_unserialize($itemid); }, $itemid);
    mini_profile("Generated clone", function ($itemid) { return test_generated_clone($itemid); }, $itemid);
    mini_profile("Virtual baseline", function ($itemid) { return test_virtual_baseline($itemid); }, $itemid);
    mini_profile("Virtual unserialize", function ($itemid) { return test_virtual_unserialize($itemid); }, $itemid);
    mini_profile("Virtual clone", function ($itemid) { return test_virtual_clone($itemid); }, $itemid);
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
    xar::tpl()->init();
    var_dump($sample->name->showOutput());
}

$itemid = null;
//$itemid = 1;
run_profile($itemid);

//test_crud();
//test_list();
//test_properties();
