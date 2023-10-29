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
 * This property displays the queued result of a callable function as value (experimental - do not use in production)
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
    /** @var array<string, array<int, mixed>> */
    protected static $_queued = [];
    /** @var array<string, array<mixed>> */
    protected static $_handled = [];

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime - re-use *-callable.xt templates here too for now
        $this->tplmodule = 'dynamicdata';
        $this->template = 'callable';
        $this->filepath = 'modules/dynamicdata/xarproperties';
    }

    /**
     * Call 'setter' function and set value to 'setter' function
     * @param mixed $itemid
     * @param mixed $value
     * @return mixed
     */
    protected function callFunctions($itemid, $value)
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
            return false;
        }
        return true;
    }

    /**
     * Summary of getQueueName
     * @return string
     */
    public function getQueueName()
    {
        if (empty($this->callable_queue)) {
            $queue = 'dd_' . $this->id;
        } else {
            $queue = $this->callable_queue;
        }
        // initialize queue if needed
        static::$_queued[$queue] ??= [];
        return $queue;
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
        // keep track of which values have been set before we get them (batch)
        if (!$this->hasQueueValue($itemid, $value)) {
            $queue = $this->getQueueName();
            throw new Exception('Unexpected value ' . var_export($value, true) . ' in callable queue ' . $queue);
        }
        return $value;
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
}
