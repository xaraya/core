<?php
/**
 * The Callable property sets a callable as value in setItemValue() with the itemid and value
 * as inherited variables for use in the anonymous function, method or whatever the callable is.
 *
 * The callable gets invoked in showOutput() and will set the actual value for output. This could
 * serve as a generalisation of the deferitem, deferlist, defermany properties, but it needs 2
 * configurable functions to work: one called in setItemValue() and one used in the callable itself.
 *
 * A 3rd callable could be used to preset any options for showInput() if relevant, and a 4th would
 * be needed to reproduce the different behaviours of deferlist and defermany, which is becoming
 * a bit too much if we want to keep this as a generic property :-(
 *
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

namespace Xaraya\DataObject\Properties;

use DataProperty;
use ObjectDescriptor;
use SimpleXMLElement;
use Exception;
use JsonException;
use sys;

/* Include parent class */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * This property displays the result of a callable function as value
 * It is extended by QueuedProperty for practical batch operations
 */
class CallableProperty extends DataProperty
{
    public $id         = 18270;
    public $name       = 'callable';
    public $desc       = 'Callable (test)';
    public $reqmodules = ['dynamicdata'];
    /** @var array<mixed> */
    public $options    = [];
    // override default configuration types here
    /** @var list<string> */
    public $configurationtypes = ['callable'];
    /** @var callable */
    protected $callable_getter;
    /** @var callable */
    protected $callable_setter;
    /** @var callable */
    protected $callable_options;
    /** @var callable */
    protected $callable_input;
    /** @var callable */
    protected $callable_output;
    /** @var bool|string */
    protected $callable_debug     = false;
    /** @var bool|string */
    protected $callable_trace     = false;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime
        $this->tplmodule = 'dynamicdata';
        $this->template = 'callable';
        $this->filepath = 'modules/dynamicdata/xarproperties';
    }

    /**
     * Check if the configurable callable is valid, and replace $this string with $this value
     *
     * @param string $type getter, setter or options
     * @return bool
     */
    public function checkCallable($type)
    {
        $callable = 'callable_' . $type;
        if (empty($this->{$callable})) {
            return false;
        }
        $this->{$callable} = $this->decodeCallableValue($this->{$callable});
        if (is_callable($this->{$callable})) {
            return true;
        } elseif (is_string($this->{$callable}) && is_callable(__NAMESPACE__ . '\\' . $this->{$callable})) {
            // call function in current namespace
            $this->{$callable} = __NAMESPACE__ . '\\' . $this->{$callable};
            return true;
        } elseif (is_array($this->{$callable}) && is_string($this->{$callable}[0]) && is_callable([__NAMESPACE__ . '\\' . $this->{$callable}[0], $this->{$callable}[1]])) {
            // call static class method in current namespace
            $this->{$callable} = [__NAMESPACE__ . '\\' . $this->{$callable}[0], $this->{$callable}[1]];
            return true;
        }
        if ($this->callable_debug) {
            echo 'Warning: Callable "' . $type . '" is not callable: ' . var_export($this->{$callable}, true);
        }
        return false;
    }

    /**
     * Example of callable 'setter' method
     * Configuration: [$this,"setter"]
     * @param mixed $itemid
     * @param mixed $value
     * @param bool $debug
     * @return void
     */
    public function setter($itemid, $value, $debug = false)
    {
        if ($debug) {
            echo 'Setter method for ' . $itemid . ' value ' . var_export($value, true);
        }
    }

    /**
     * Example of callable 'getter' method
     * Configuration: [$this,"getter"]
     * @param mixed $itemid
     * @param mixed $value
     * @param bool $debug
     * @return mixed
     */
    public function getter($itemid, $value, $debug = false)
    {
        if ($debug) {
            echo 'Getter method for ' . $itemid . ' value ' . var_export($value, true);
        }
        return $value;
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
                $options[] = ['id' => $value['id'], 'name' => json_encode($value, JSON_NUMERIC_CHECK)];
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
        return $data;
    }

    /**
     * Call 'setter' function and set value to callable 'getter' function for later
     * @todo see also defermany where $itemid actually matters and $value does not, except in setValue() for preview
     * @param mixed $itemid
     * @param mixed $value
     * @return mixed
     */
    protected function callFunctions($itemid, $value)
    {
        if ($this->checkCallable('setter')) {
            call_user_func($this->callable_setter, $itemid, $value, $this->callable_debug);
        }
        if ($this->checkCallable('getter')) {
            // Note: $this is inherited by default, but we use $itemid and $value here
            $value = function () use ($itemid, $value) {
                return call_user_func($this->callable_getter, $itemid, $value, $this->callable_debug);
            };
        }
        return $value;
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * @return mixed the value for the property
     */
    public function getValue()
    {
        // 1. in showDisplay() get value from property - see showOutput()
        // 2. in showForm() set for input preview and update with setValue() - call 'getter' here
        //$this->log_trace();
        if (!empty($this->value) && is_callable($this->value)) {
            $this->value = call_user_func($this->value);
        }
        return $this->value;
        //return parent::getValue();
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param mixed $value the new value for the property
     * @return void
     */
    public function setValue($value = null)
    {
        // 1. in construct() set to defaultvalue - skip
        // 2. in showForm() set for input preview and update - call 'setter' here
        // 3. in showDisplay() get value from property - see showOutput()
        //$this->log_trace();
        if (!empty($this->_itemid) && !empty($value)) {
            $itemid = $this->_itemid;
            $value = $this->callFunctions($itemid, $value);
        }
        //$this->value = $value;
        parent::setValue($value);
    }

    /**
     * Get the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid the item id we want the value for
     * @return mixed
     */
    public function getItemValue($itemid)
    {
        // already called 'setter' in setItemValue
        //$this->log_trace();
        //return $this->_items[$itemid][$this->name];
        return parent::getItemValue($itemid);
    }

    /**
     * Set the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid
     * @param mixed $value
     * @param integer $fordisplay
     * @return void
     */
    public function setItemValue($itemid, $value, $fordisplay = 0)
    {
        // 1. in getItems() set to value from datastore - call 'setter' and set value to 'getter'
        if (!empty($itemid) && !empty($value)) {
            $value = $this->callFunctions($itemid, $value);
        }
        //$this->log_trace();
        //$this->value = $value;
        //$this->_items[$itemid][$this->name] = $this->value;
        parent::setItemValue($itemid, $value, $fordisplay);
    }

    /**
     * Show an input field for setting/modifying the value of this property
     *
     * @param array<string, mixed> $data
     * with
     *     $data['name'] name of the field (default is 'dd_NN' with NN the property id)
     *     $data['value'] value of the field (default is the current value)
     *     $data['id'] id of the field
     *     $data['tabindex'] tab index of the field
     *     $data['module'] which module is responsible for the templating
     *     $data['template'] what's the partial name of the showinput template.
     *     $data[*] rest of arguments is passed on to the templating method.
     *
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showInput(array $data = [])
    {
        // do we want to call this here, maybe as $callable_options?
        if (!isset($data['options']) && $this->checkCallable('options')) {
            $data['options'] = $this->getOptions($data);
        }
        // @checkme we *don't* really want to retrieve the data based on the value here - extended in defermany
        //$data = $this->getDeferredData($data);
        //$this->log_trace();
        //if(!isset($data['value']))       $data['value']    = $this->value;
        if (!empty($data['value']) && $this->checkCallable('input')) {
            $data = call_user_func($this->callable_input, $data, $this->callable_debug);
        }
        return parent::showInput($data);
    }

    /**
     * Show some default output for this property
     *
     * @param array<string, mixed> $data
     * with
     *     mixed $data['value'] value of the property (default is the current value)
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showOutput(array $data = [])
    {
        // 1. in showView() get value from data = from objectlist via getItemValue/setItemValue - skip
        // 2. in showDisplay() get value from property - not set via setValue in datastore - call 'setter' first?
        if (isset($data['value'])) {
            $value = $data['value'];
        } else {
            // @todo call 'setter' first?
            if (!empty($this->value) && !is_callable($this->value)) {
                $this->setValue($this->value);
                //$this->value = $this->callFunctions($this->_itemid, $this->value);
            }
            $value = $this->getValue();
        }
        // call 'getter' function defined in setItemValue() - with inherited $itemid and $value
        if (!empty($value) && is_callable($value)) {
            $data['value'] = call_user_func($value);
        } else {
            $data['value'] = $value;
        }
        //$this->log_trace();
        if (!empty($data['value']) && $this->checkCallable('output')) {
            $data = call_user_func($this->callable_output, $data, $this->callable_debug);
        }
        // if $data['value'] is null, DataProperty::showOutput() checks $this->value which is still callable from the last setItemValue()
        // -> Exception in showoutput-callable.xt template
        return parent::showOutput($data);
    }

    /**
     * Show a hidden field for this property
     *
     * @param array<string, mixed> $data
     * with
     *     $data['name'] name of the field (default is 'dd_NN' with NN the property id)
     *     $data['value'] value of the field (default is the current value)
     *     $data['id'] id of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showHidden(array $data = [])
    {
        if (!empty($data['value']) && is_array($data['value'])) {
            $data['value'] = json_encode($data['value'], JSON_NUMERIC_CHECK);
        }
        return parent::showHidden($data);
    }

    /**
     * Summary of importValue
     * @param \SimpleXMLElement $element
     * @return mixed
     */
    public function importValue(SimpleXMLElement $element)
    {
        // return $this->castType((string)$element->{$this->name});
        return parent::importValue($element);
    }

    /**
     * Export the value of itemprop1 here, but don't return the propname values from Called1
     * @param mixed $itemid
     * @param mixed $item
     * @return mixed
     */
    public function exportValue($itemid, $item)
    {
        // return xarVar::prepForDisplay($item[$this->name]);
        // $data = $this->getDeferredData(['value' => $item[$this->name], '_itemid' => $itemid]);
        return parent::exportValue($itemid, $item);
    }

    /**
     * Summary of createValue
     * @param mixed $itemid
     * @return void
     */
    public function createValue($itemid = 0)
    {
        // $itemid is still unknown at this point, since this is called before datastore->createItem()
        if (empty($this->value)) {
            return;
        }
        if (is_callable($this->value)) {
            $this->value = call_user_func($this->value);
        }
    }

    /**
     * Summary of updateValue
     * @param mixed $itemid
     * @return void
     */
    public function updateValue($itemid = 0)
    {
        if (empty($itemid) || empty($this->value)) {
            return;
        }
        if (is_callable($this->value)) {
            $this->value = call_user_func($this->value);
        }
    }

    /**
     * Summary of deleteValue
     * @param mixed $itemid
     * @return void
     */
    public function deleteValue($itemid = 0)
    {
        if (empty($itemid) || empty($this->value)) {
            return;
        }
        if (is_callable($this->value)) {
            $this->value = call_user_func($this->value);
        }
    }

    /**
     * Retrieve the list of options on demand - only used for showInput() here, not validateValue() or elsewhere
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function getOptions($data = [])
    {
        if (count($this->options) > 0) {
            return $this->options;
        }

        $this->options = [];
        if ($this->checkCallable('options')) {
            $itemid = $this->_itemid;
            $value = $data['value'] ?? $this->getValue();
            $this->options = call_user_func($this->callable_options, $itemid, $value, $this->callable_debug);
        }
        return $this->options;
    }

    /**
     * Parse the configuration rule - replace $this string with $this value here?
     *
     * @param string|array<mixed> $configuration
     * @return array<string, mixed>
     */
    public function parseConfiguration($configuration = '')
    {
        // Return the exploded fields
        $fields = parent::parseConfiguration($configuration);
        return $fields;
    }

    /**
     * Show the current configuration rule in a specific form for this property type - set callable config
     *
     * @param array<string, mixed> $data
     * with
     *     $data['name'] name of the field (default is 'dd_NN' with NN the property id)
     *     $data['configuration'] configuration rule (default is the current configuration)
     *     $data['id'] id of the field
     *     $data['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showConfiguration(array $data = [])
    {
        // make sure we set the properties before calling getConfigProperties()
        if (!empty($data['configuration'])) {
            $this->parseConfiguration($data['configuration']);
        }
        // skip default config properties in base property
        $data['display'] ??= [];
        $data['validation'] ??= [];
        $data['initialization'] ??= [];
        // set callable config properties
        $data['callable'] = $this->getConfigProperties('callable', 1);
        return parent::showConfiguration($data);
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
        $configproperties = [];
        $proplist = ['getter', 'setter', 'options', 'input', 'output'];
        foreach ($proplist as $prop) {
            $name = 'callable_' . $prop;
            $configproperties[$name] = [
                'name' => $name,
                'label' => ucfirst($prop),
                'description' => 'Callable ' . ucfirst($prop) . ' = dynamicdata_callable_' . $prop . ' or [$this,&quot;' . $prop . '&quot;] or [$this->objectref,&quot;methodName&quot;] etc.',
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
        $proplist = ['debug', 'trace'];
        foreach ($proplist as $prop) {
            $name = 'callable_' . $prop;
            $configproperties[$name] = [
                'name' => $name,
                'label' => ucfirst($prop),
                'description' => 'Show ' . $prop . ' output',
                'property_id' => 14,  // checkbox
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
        $confignames = [];
        $proplist = ['getter', 'setter', 'options', 'input', 'output', 'debug', 'trace'];
        foreach ($proplist as $prop) {
            $confignames[] = 'callable_' . $prop;
        }
        return $confignames;
    }

    /**
     * Summary of encodeCallableValue
     * @param mixed $value
     * @return mixed
     */
    public function encodeCallableValue($value)
    {
        if (!empty($value) && is_array($value)) {
            $class = $value[0];
            if (is_object($class)) {
                $value = ['$this', $value[1]];
            }
            $value = json_encode($value, JSON_HEX_QUOT);
        }
        return $value;
    }

    /**
     * Summary of decodeCallableValue
     * @param mixed $value
     * @return mixed
     */
    public function decodeCallableValue($value)
    {
        // [$this,"method"] or [$this->objectref,"method"] or callable_function or ["className","staticMethod"]
        if (is_string($value) && str_starts_with($value, '[')) {
            // add quotes around $this and $this->objectref if needed
            if (str_starts_with($value, '[$this->objectref')) {
                $value = str_replace('$this->objectref', '"$this->objectref"', $value);
            } elseif (str_starts_with($value, '[$this')) {
                $value = str_replace('$this', '"$this"', $value);
            }
            try {
                $decoded = json_decode($value, true, 2, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                echo $e->getMessage();
                return false;
            }
            $value = $decoded;
        }
        if (is_array($value) && is_string($value[0])) {
            $class = $value[0];
            if ($class == '$this->objectref') {
                $value[0] = $this->objectref;
            } elseif ($class == '$this') {
                $value[0] = $this;
            }
        }
        return $value;
    }

    /**
     * Summary of log_trace
     * @return void
     */
    public function log_trace()
    {
        if (empty($this->callable_trace)) {
            return;
        }
        try {
            $trace = debug_backtrace(2, 3);
            array_shift($trace);
            $caller = array_shift($trace);
            print_r("<pre>Caller: " . $this->name . ' (' . $this->_itemid . ")\n");
            print_r($caller);
            //print_r("\nTrace:\n");
            //print_r($trace);
            print_r("</pre>");
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }

    /**
     * Example of callable static class method (not for real use)
     * Configuration: ["CallableProperty","hello"] or ["\\Xaraya\\DataObject\\Properties\\CallableProperty","hello"]
     * @param array<mixed> $args
     * @return void
     */
    public static function hello(...$args)
    {
        echo 'Hello: ' . var_export($args, true);
    }
}

/**
 * Example of callable 'setter' function
 * Configuration: dynamicdata_callable_setter or \Xaraya\DataObject\Properties\dynamicdata_callable_setter
 * @param mixed $itemid
 * @param mixed $value
 * @param bool $debug
 * @return void
 */
function dynamicdata_callable_setter($itemid, $value, $debug = false)
{
    if ($debug) {
        echo 'Setter function for ' . $itemid . ' value ' . var_export($value, true);
    }
}

/**
 * Example of callable 'getter' function
 * Configuration: dynamicdata_callable_getter or \Xaraya\DataObject\Properties\dynamicdata_callable_getter
 * @param mixed $itemid
 * @param mixed $value
 * @param bool $debug
 * @return mixed
 */
function dynamicdata_callable_getter($itemid, $value, $debug = false)
{
    if ($debug) {
        echo 'Getter function for ' . $itemid . ' value ' . var_export($value, true);
    }
    return $value;
}

/**
 * Example of callable 'options' function
 * Configuration: dynamicdata_callable_options or \Xaraya\DataObject\Properties\dynamicdata_callable_options
 * @param mixed $itemid optional
 * @param mixed $value optional
 * @param bool $debug
 * @return array<mixed>
 */
function dynamicdata_callable_options($itemid = null, $value = null, $debug = false)
{
    $options = [];
    if ($debug) {
        echo 'Options function for ' . $itemid . ' value ' . var_export($value, true);
        $options[] = ['id' => 0, 'name' => 'callable function'];
    }
    if (!empty($value)) {
        $options[] = ['id' => $value, 'name' => $value];
    }
    return $options;
}
