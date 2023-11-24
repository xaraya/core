<?php
/**
 * The Mapper property extends the Queued property by mapping queued values
 * to an itemid of some configurable DataObject and returning some of its
 * properties from cache (batch) - see deferitem etc. (WIP)
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
sys::import('modules.dynamicdata.xarproperties.queued');

/**
 * The mapper can be set to automatically load related object properties if the value contains its itemid
 *
 * ```
 * Format:
 *     dataobject:<objectname> (= for display link only)
 *     dataobject:<objectname>.<propname>
 *  or dataobject:<objectname>.<propname>,<propname2>,<propname3>
 * Example:
 *     dataobject:sample:name will show the sample name if the property contains the sample id
 *  or dataobject:sample:name,age,location will show the name,age,location if the property contains the sample id
 * ```
 */
class MapperProperty extends QueuedProperty
{
    public $id         = 18272;
    public $name       = 'mapper';
    public $desc       = 'Queued Mapper (test)';
    public $reqmodules = ['dynamicdata'];
    public $options    = [];
    /** @var string */
    public $objectname = '';
    /** @var list<string> */
    public $fieldlist  = [];
    /** @var string|null */
    public $displaylink = null;
    /** @var bool */
    public $singlevalue = false;
    // mapper configuration
    /** @var string|null */
    protected $callable_mapper = null;
    /** @var array<string, \DataObjectLoader> */
    protected static $_mapper = [];

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime - re-use *-callable.xt templates here too for now
        $this->parseConfigValue($this->callable_mapper);
        // use default 'options' method if not specified
        if (empty($this->callable_options)) {
            $this->callable_options = [$this, 'options'];
        }
        // use default 'input' method if not specified
        if (empty($this->callable_input)) {
            $this->callable_input = [$this, 'input'];
        }
        // use default 'output' method if not specified
        if (empty($this->callable_output)) {
            $this->callable_output = [$this, 'output'];
        }
    }

    /**
     * Summary of getMapper
     * @return \DataObjectLoader
     */
    public function getMapper()
    {
        $queue = $this->getQueueName();
        if (empty(static::$_mapper[$queue])) {
            if (!empty($this->fieldlist)) {
                static::$_mapper[$queue] = new \DataObjectItemLoader($this->objectname, $this->fieldlist);
            } else {
                static::$_mapper[$queue] = new \DataObjectDummyLoader($this->objectname, $this->fieldlist);
            }
        }
        return static::$_mapper[$queue];
    }

    /**
     * The mapper can be set to automatically load object properties if the value contains its itemid
     * @param string|null $value the mapper used to configure the dataobject resolver function
     * @return void
     */
    public function parseConfigValue($value)
    {
        if (empty($value) || substr($value, 0, 11) !== 'dataobject:') {
            return;
        }
        $objectpart = substr($value, 11);
        // make sure we always have at least two parts here
        [$object, $field] = explode('.', $objectpart . '.');
        if (empty($object)) {
            return;
        }
        $this->setQueueName($objectpart);
        // dataobject:<objectname> (= for display link only) uses empty fieldlist []
        // dataobject:<objectname>.<propname> uses fieldlist ['propname']
        // dataobject:<objectname>.<propname>,<propname2>,<propname3> uses fieldlist ['propname', 'propname2', ...]
        $fieldlist = array_filter(explode(',', $field));
        $this->objectname = $object;
        $this->fieldlist = $fieldlist;
        // see if we can use a fixed template for display links here
        $this->displaylink = \xarServer::getObjectURL($object, 'display', ['itemid' => '[itemid]']);
        if (strpos($this->displaylink, '[itemid]') === false) {
            // sorry, you'll have to deal with it directly in the template
            $this->displaylink = null;
        }
    }

    /**
     * Example of callable 'batch' method using mapper
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
        // basic 'batch' operation using mapper
        $this->getMapper()->addList($values);
        $this->getMapper()->load();
        $items = $this->getMapper()->getList($values);
        foreach ($items as $key => $value) {
            // set result for value = value here
            if (isset($value)) {
                $result[$this->getCacheKey($key)] = $value;
            } else {
                $result[$this->getCacheKey($key)] ??= $key;
                // if $data['value'] is null, DataProperty::showOutput() checks $this->value which is still callable from the last setItemValue()
                // -> Exception in showoutput-callable.xt template
                //$result[$key] ??= null;
                //$result[$key] ??= '';
            }
        }
        // clear queue
        $values = [];
        return count($result);
    }

    /**
     * Example of callable 'options' method
     * Configuration: [$this,"options"]
     * @param mixed $itemid optional
     * @param mixed $value optional
     * @param bool $debug
     * @return array<mixed>
     */
    public function options($itemid = null, $value = null, $debug = false)
    {
        $options = [];
        if ($debug) {
            echo 'Options method for ' . $itemid . ' value ' . var_export($value, true);
            $options[] = ['id' => 0, 'name' => 'callable method'];
        }
        if (!empty($value)) {
            if (is_array($value)) {
                $field = $this->fieldlist[0];
                $options[] = ['id' => $value['id'], 'name' => $value[$field]];
            } else {
                $options[] = ['id' => $value, 'name' => $value];
            }
        }
        return $options;
    }

    /**
     * Example of callable 'input' method
     * Configuration: [$this,"input"]
     * @param array<string, mixed> $data
     * @param bool $debug
     * @return array<string, mixed>
     */
    public function input($data, $debug = false)
    {
        if ($debug) {
            echo 'Input method for data';
        }
        if (!$this->singlevalue && !empty($this->fieldlist) && count($this->fieldlist) == 1) {
            $this->singlevalue = true;
        }
        // @todo pass along source id (instead of value?)
        return $data;
    }

    /**
     * Example of callable 'output' method
     * Configuration: [$this,"output"]
     * @param array<string, mixed> $data
     * @param bool $debug
     * @return array<string, mixed>
     */
    public function output($data, $debug = false)
    {
        if ($debug) {
            echo 'Output method for data';
        }
        if (!$this->singlevalue && !empty($this->fieldlist) && count($this->fieldlist) == 1) {
            $this->singlevalue = true;
        }
        if ($this->singlevalue && is_array($data['value']) && array_key_exists($this->fieldlist[0], $data['value'])) {
            if (array_key_exists('id', $data['value'])) {
                $data['source'] = $data['value']['id'];
            }
            $field = $this->fieldlist[0];
            $data['value'] = $data['value'][$field];
            $data['singlevalue'] = true;
        }
        if (!empty($this->displaylink) && !empty($data['value']) && !empty($data['source'])) {
            $data['link'] = str_replace('[itemid]', (string) $data['source'], $this->displaylink);
        }
        return $data;
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
        // get queued config properties
        $configproperties = parent::getConfigProperties($type, $fullname);
        // add mapper config property
        $proplist = ['mapper'];
        foreach ($proplist as $prop) {
            $name = 'callable_' . $prop;
            $configproperties[$name] = [
                'name' => $name,
                'label' => ucfirst($prop),
                'description' => 'Mapper configuration (see deferitem etc.)',
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
        $proplist = ['mapper'];
        foreach ($proplist as $prop) {
            $confignames[] = 'callable_' . $prop;
        }
        return $confignames;
    }
}

/**
 * Example of callable 'mapper' function = set everything from queue in cache :-)
 * Configuration: dynamicdata_callable_mapper or \Xaraya\DataObject\Properties\dynamicdata_callable_mapper
 * @param array<int, mixed> $values list of values to be resolved (current queue by reference)
 * @param array<string, mixed> $result assoc array of result by value (current cache by reference)
 * @param bool $debug show some debug messages or not
 * @return int
 */
function dynamicdata_callable_mapper(&$values, &$result, $debug = false)
{
    if ($debug) {
        echo 'Batch method for ' . count($values) . ' values';
    }
    // basic 'mapper' operation = set everything from queue in cache :-)
    foreach ($values as $value) {
        // set result for value = value here
        $key = "'$value'";
        $result[$key] ??= $value;
    }
    // clear queue
    $values = [];
    return count($result);
}
