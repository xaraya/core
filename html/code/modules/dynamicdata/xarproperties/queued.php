<?php
/**
 * The Queued property extends the Callable property by keeping track of which values
 * have been set/get by which property, or in which common queue if they're shared,
 * before we get them (batch)
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

namespace Xaraya\DataObject\Properties;

use ObjectDescriptor;
use Exception;
use sys;

/* Include parent class */
sys::import('modules.dynamicdata.xarproperties.callable');

/**
 * This property displays the queued result of a callable function as value
 * The 'setter', 'getter' and 'batch' callables will be preset for basic *batch operation* if not defined in your property configuration
 *
 * In principle you only need to override or configure the 'batch' method for practical use:
 * ```
 *    /**
 *     * Example of callable 'batch' method = set everything from queue in cache :-)
 *     * Configuration: [$this,"batch"]
 *     * @param array<int, mixed> $values list of values to be resolved (current queue by reference)
 *     * @param array<mixed> $result assoc array of result by value (current cache by reference)
 *     * @param bool $debug show some debug messages or not
 *     * @return int
 *     * /
 *    public function batch(&$values, &$result, $debug = false)
 *    {
 *        if ($debug) {
 *            echo 'Batch method for ' . count($values) . ' values';
 *        }
 *        // basic 'batch' operation = set everything from queue in cache :-)
 *        foreach ($values as $value) {
 *            // set result for value = value here
 *            $result[$value] ??= $value;
 *        }
 *        // clear queue
 *        $values = [];
 *        return count($result);
 *    }
 * ```
 */
class QueuedProperty extends CallableProperty
{
    public $id         = 18271;
    public $name       = 'queued';
    public $desc       = 'Queued Callable (test)';
    public $reqmodules = ['dynamicdata'];
    public $options    = [];
    // keep track of which values have been set/get by which property
    /** @var string|null */
    protected $callable_queue = null;
    /** @var callable */
    protected $callable_batch;
    /** @var string|null */
    protected $queue_name = null;
    /** @var array<string, list<mixed>> */
    protected static $_queued = [];
    /** @var array<string, array<string, mixed>> */
    protected static $_cached = [];

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime - re-use *-callable.xt templates here too for now
        $this->tplmodule = 'dynamicdata';
        $this->template = 'callable';
        $this->filepath = 'modules/dynamicdata/xarproperties';

        // use default 'setter' and 'getter' methods if not specified
        if (empty($this->callable_setter)) {
            $this->callable_setter = [$this, 'setter'];
        }
        if (empty($this->callable_getter)) {
            $this->callable_getter = [$this, 'getter'];
        }
        // use default 'batch' method if not specified
        if (empty($this->callable_batch)) {
            $this->callable_batch = [$this, 'batch'];
        }
    }

    /**
     * Re-initialize queue and cache if needed
     * @return void
     */
    public function __wakeup()
    {
        $queue = $this->getQueueName();
        static::$_queued[$queue] = [];
        static::$_cached[$queue] = [];
    }

    public function setter($itemid, $value, $debug = false)
    {
        // keep track of which values have been set before we get them (batch)
        $this->addQueueValue($itemid, $value);
        parent::setter($itemid, $value, $debug);
    }

    public function getter($itemid, $value, $debug = false)
    {
        $value = $this->getQueueValue($itemid, $value);
        return parent::getter($itemid, $value, $debug);
    }

    /**
     * Example of callable 'batch' method = set everything from queue in cache :-)
     * Configuration: [$this,"batch"]
     * @param list<int|string> $values list of values to be resolved (current queue by reference)
     * @param array<string, mixed> $result assoc array of result by value (current cache by reference)
     * @param bool $debug show some debug messages or not
     * @return int
     */
    public function batch(&$values, &$result, $debug = false)
    {
        if ($debug) {
            echo 'Batch method for ' . count($values) . ' values';
        }
        // basic 'batch' operation = set everything from queue in cache :-)
        foreach ($values as $value) {
            // set result for value = value here
            $result[$this->getCacheKey($value)] ??= $value;
        }
        // clear queue
        $values = [];
        return count($result);
    }

    /**
     * Call 'setter' function and set value to callable 'getter' function for later
     * @todo see also defermany where $itemid actually matters and $value does not, except in setValue() for preview
     * @param mixed $itemid
     * @param mixed $value
     * @return mixed
     */
    protected function callFunctions_Unused($itemid, $value)
    {
        // keep track of which values have been set before we get them (batch)
        $this->addQueueValue($itemid, $value);
        if ($this->checkCallable('setter')) {
            call_user_func($this->callable_setter, $itemid, $value, $this->callable_debug);
        }
        if ($this->checkCallable('getter')) {
            // Note: $this is inherited by default, but we use $itemid and $value here
            $value = function () use ($itemid, $value) {
                $value = $this->getQueueValue($itemid, $value);
                return call_user_func($this->callable_getter, $itemid, $value, $this->callable_debug);
            };
        }
        return $value;
    }

    /**
     * Summary of hasQueueValue
     * @param mixed $itemid
     * @param mixed $value
     * @return bool
     */
    public function hasQueueValue($itemid, $value)
    {
        // keep track of which values have been set before we get them (batch)
        $queue = $this->getQueueName();
        if (!empty($value) && !in_array($value, static::$_queued[$queue])) {
            if (empty(static::$_cached[$queue]) || !$this->hasCacheValue($itemid, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Summary of getQueueName
     * @return string
     */
    public function getQueueName()
    {
        if (isset($this->queue_name)) {
            return $this->queue_name;
        }
        if (empty($this->callable_queue)) {
            $queue = 'dd_' . $this->id;
        } else {
            $queue = $this->callable_queue;
        }
        $this->setQueueName($queue);
        return $queue;
    }

    /**
     * Summary of setQueueName
     * @param string $queue
     * @return void
     */
    public function setQueueName($queue)
    {
        $this->queue_name = $queue;
        // initialize queue if needed
        static::$_queued[$queue] ??= [];
        static::$_cached[$queue] ??= [];
    }

    /**
     * Summary of addQueueValue
     * @param mixed $itemid
     * @param mixed $value
     * @return void
     */
    public function addQueueValue($itemid, $value)
    {
        // keep track of which values have been set before we get them (batch)
        if (!$this->hasQueueValue($itemid, $value)) {
            $queue = $this->getQueueName();
            static::$_queued[$queue][] = $value;
        }
    }

    /**
     * Summary of getQueueValue
     * @param mixed $itemid
     * @param mixed $value
     * @throws \Exception
     * @return mixed
     */
    public function getQueueValue($itemid, $value)
    {
        if (empty($value)) {
            return $value;
        }
        // batch handling of values in $_queued[$queue]
        $queue = $this->getQueueName();
        if ($this->checkCallable('batch') && $this->countQueueValues() > 0) {
            // we want to pass by reference, so use call_user_func_array() approach here
            //$count = call_user_func($this->callable_batch, static::$_queued[$queue], static::$_cached[$queue], $this->callable_debug);
            $count = call_user_func_array($this->callable_batch, [&static::$_queued[$queue], &static::$_cached[$queue], $this->callable_debug]);
            if ($this->callable_debug) {
                echo 'Batch call result (' . $count . ') = ' . count(static::$_queued[$queue]) . ' queued, ' . count(static::$_cached[$queue]) . ' cached';
            }
        }
        if (!$this->hasCacheValue($itemid, $value)) {
            throw new Exception('Unexpected value ' . var_export($value, true) . ' in callable queue ' . $queue);
        }
        return $this->getCacheValue($itemid, $value);
        //return $value;
    }

    /**
     * Summary of countQueueValues
     * @return int
     */
    public function countQueueValues()
    {
        $queue = $this->getQueueName();
        return count(static::$_queued[$queue]);
    }

    /**
     * Summary of getCacheKey
     * @param mixed $value
     * @return string
     */
    public function getCacheKey($value)
    {
        return "'$value'";
        //return (string) $value;
    }

    /**
     * Summary of hasCacheValue
     * @param mixed $itemid
     * @param mixed $value
     * @return bool
     */
    public function hasCacheValue($itemid, $value)
    {
        $queue = $this->getQueueName();
        if (!array_key_exists($this->getCacheKey($value), static::$_cached[$queue])) {
            return false;
        }
        return true;
    }

    /**
     * Summary of getCacheValue
     * @param mixed $itemid
     * @param mixed $value
     * @return mixed
     */
    public function getCacheValue($itemid, $value)
    {
        $queue = $this->getQueueName();
        return static::$_cached[$queue][$this->getCacheKey($value)];
    }

    /**
     * Summary of countCacheValues
     * @return int
     */
    public function countCacheValues()
    {
        $queue = $this->getQueueName();
        return count(static::$_cached[$queue]);
    }

    /**
     * Return the configuration options for this property - don't bother looking up in database
     *
     * @param string $type:  type of option (display, initialization, validation) - callable here
     * @param int|bool $fullname: return the full name asa key, e.g. "display_size - always 1
     * @return array<mixed> of configuration options
     */
    public function getConfigProperties($type = "", $fullname = 0)
    {
        // get callable config properties
        $configproperties = parent::getConfigProperties($type, $fullname);
        // add callable batch method
        $proplist = ['batch'];
        foreach ($proplist as $prop) {
            $name = 'callable_' . $prop;
            $configproperties[$name] = [
                'name' => $name,
                'label' => ucfirst($prop),
                'description' => 'Callable ' . ucfirst($prop) . ' = [$this,&quot;' . $prop . '&quot;] or [$this->objectref,&quot;methodName&quot;] etc.',
                'property_id' => 2,  // textbox
                'ignore_empty' => true,
                'configuration' => null,
            ];
            // not sure it's ever called without the fullname :-)
            $key = $fullname ? $name : $prop;
            $configproperties[$key]['value'] = $this->encodeCallableValue($this->{$name});
            $configproperties[$key]['shortname'] = $prop;
            $configproperties[$key]['fullname'] = $name;
        }
        // add queue name config property
        $proplist = ['queue'];
        foreach ($proplist as $prop) {
            $name = 'callable_' . $prop;
            $configproperties[$name] = [
                'name' => $name,
                'label' => ucfirst($prop),
                'description' => 'Use common queue name to share with other properties',
                'property_id' => 2,  // textbox
                'ignore_empty' => true,
                'configuration' => null,
                'value' => $this->{$name},
                'shortname' => $prop,
                'fullname' => $name,
            ];
        }
        return $configproperties;
    }

    public function listConfigProperties()
    {
        $confignames = parent::listConfigProperties();
        $proplist = ['batch', 'queue'];
        foreach ($proplist as $prop) {
            $confignames[] = 'callable_' . $prop;
        }
        return $confignames;
    }
}

/**
 * Example of callable 'batch' function = set everything from queue in cache :-)
 * Configuration: dynamicdata_callable_batch or \Xaraya\DataObject\Properties\dynamicdata_callable_batch
 * @param list<int|string> $values list of values to be resolved (current queue by reference)
 * @param array<string, mixed> $result assoc array of result by value (current cache by reference)
 * @param bool $debug show some debug messages or not
 * @return int
 */
function dynamicdata_callable_batch(&$values, &$result, $debug = false)
{
    if ($debug) {
        echo 'Batch method for ' . count($values) . ' values';
    }
    // basic 'batch' operation = set everything from queue in cache :-)
    foreach ($values as $value) {
        // set result for value = value here
        $key = "'$value'";
        $result[$key] ??= $value;
    }
    // clear queue
    $values = [];
    return count($result);
}
