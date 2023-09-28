<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.dynamicdata.xarproperties.objectref');
/**
 * This property displays a multiselect of items of a dataobject
 */
class ObjectMultiSelectProperty extends ObjectRefProperty
{
    public $id         = 30131;
    public $name       = 'objectmultiselect';
    public $desc       = 'Object Multiselect';

    public $validation_single = false;
    public $validation_allowempty = false;
    public $validation_single_invalid; // CHECKME: is this a validation or something else?
    public $validation_allowempty_invalid;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'dynamicdata';
        $this->template =  'objectmultiselect';
    }

    /**
     * Get the value of a multiselect from a web page
     *
     * @param  string $name The name of the multiselect
     * @param  string $value The value of the multiselect
     * @return bool   This method passes the value gotten to the validateValue method and returns its output.
     */
    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        $this->invalid = '';
        if(!isset($value)) {
            [$found, $value] = $this->fetchValue($name);
            if (!$found) {
                $value = null;
            }
        }
        return $this->validateValue($value);
    }

    /**
     * Validate the value of a multiselect
     *
     * @return bool Returns true if the value passes all validation checks; otherwise returns false.
     */
    public function validateValue($value = null)
    {
        // do NOT call parent validateValue here - it will always fail !!!
        //if (!parent::validateValue($value)) return false;
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name, xarLog::LEVEL_DEBUG);

        // If we allow values not in the options, accept the current value and return
        if ($this->validation_override) {
            $this->value = $value;
            return true;
        }

        $value = $this->getSerializedValue($value);
        $validlist = [];
        $options = $this->getOptions();
        foreach ($options as $option) {
            array_push($validlist, $option['id']);
        }
        // check if we allow values other than those in the options
        if (!$this->validation_override) {
            foreach ($value as $val) {
                if (!in_array($val, $validlist)) {
                    if (!empty($this->validation_override_invalid)) {
                        $this->invalid = xarML($this->validation_override_invalid);
                    } else {
                        $this->invalid = xarML('unallowed selection: #(1) for #(2)', $val, $this->name);
                    }
                    xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
                    $this->value = null;
                    return false;
                }
            }
        }
        $this->value = serialize($value);
        return true;
    }

    /**
     * Display a multiselect for input
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for input on a web page
     */
    public function showInput(array $data = [])
    {
        if (isset($data['single'])) {
            $this->validation_single = $data['single'];
        }
        if (isset($data['allowempty'])) {
            $this->validation_allowempty = $data['allowempty'];
        }
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }
        $data['value'] = $this->getSerializedValue($data['value']);

        return parent::showInput($data);
    }

    /**
     * Display a multiselect for output
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for output on a web page
     */
    public function showOutput(array $data = [])
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }

        $data['value'] = $this->getSerializedValue($data['value']);
        if (!isset($data['options'])) {
            $data['options'] = $this->getOptions();
        }

        return parent::showOutput($data);
    }

    /**
     * Alias for the getSerializedValue method
     * This make the property consistent with standard usage
     */
    public function getValue()
    {
        return $this->getSerializedValue($this->value);
    }

    /**
     * Alias for the getSerializedValue method
     */
    public function getItemValue($itemid)
    {
        return $this->getSerializedValue($this->_items[$itemid][$this->name]);
    }

    /**
     * Unserializes a given value
     *
     * @param string|array<mixed> $value Serialized value
     * @return array<mixed> Return unserialized value of $value param
     */
    public function getSerializedValue($value)
    {
        if (empty($value)) {
            return [];
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = [$value];
            } else {
                $value = $tmp;
            }
        }
        // return array
        return $value;
    }
}
