<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the multiselect property
 */
class MultiSelectProperty extends SelectProperty
{
    public $id         = 39;
    public $name       = 'multiselect';
    public $desc       = 'Multiselect';

    public $validation_single = false;
    public $validation_allowempty = false;
    public $validation_single_invalid; // CHECKME: is this a validation or something else?
    public $validation_allowempty_invalid;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template =  'multiselect';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        $this->invalid = '';
        if(!isset($value)) {
            list($found,$value) = $this->fetchValue($name);
            if (!$found) $value = null;
        }
       return $this->validateValue($value);
    }
    
    public function validateValue($value = null)
    {
        // do NOT call parent validateValue here - it will always fail !!!
        //if (!parent::validateValue($value)) return false;
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name);

        // If we allow values not in the options, accept the current value and return
        if ($this->validation_override) {
            $this->value = $value;
            return true;
        }

        $value = $this->getSerializedValue($value);
        $validlist = array();
        $options = $this->getOptions();
        foreach ($options as $option) {
            array_push($validlist,$option['id']);
        }
        // check if we allow values other than those in the options
        if (!$this->validation_override) {        
            foreach ($value as $val) {
                if (!in_array($val,$validlist)) {
                    if (!empty($this->validation_override_invalid)) {
                        $this->invalid = xarML($this->validation_override_invalid);
                    } else {
                        $this->invalid = xarML('unallowed selection: #(1) for #(2)', $val, $this->name);
                    }
                    xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
                    $this->value = null;
                    return false;
                }
            }
        }
        $this->value = serialize($value);
        return true;
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['single'])) $this->validation_single = $data['single'];
        if (isset($data['allowempty'])) $this->validation_allowempty = $data['allowempty'];
        if (!isset($data['value'])) $data['value'] = $this->value;
        $data['value'] = $this->getSerializedValue($data['value']);

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;

        $data['value'] = $this->getSerializedValue($data['value']);
        if (!isset($data['options'])) $data['options'] = $this->getOptions();

        return parent::showOutput($data);
    }

    public function getValue()
    {
        return $this->getSerializedValue($this->value);
    }

    public function getItemValue($itemid)
    {
        return $this->getSerializedValue($this->_items[$itemid][$this->name]);
    }

    /**
     * Unserializes a given value
     * 
     * @param string $value Serialized value
     * @return array Return unserialized value of $value param
     */
    public function getSerializedValue($value)
    {
        if (empty($value)) {
            return array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        // return array
        return $value;
    }
}
?>
