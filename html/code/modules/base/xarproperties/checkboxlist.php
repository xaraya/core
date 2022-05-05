<?php
/* include the base class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This property displays a cluster of checkboxes
 */
class CheckboxListProperty extends SelectProperty
{
    public $id         = 1115;
    public $name       = 'checkboxlist';
    public $desc       = 'Checkbox List';

    public $display_columns = 3;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'checkboxlist';
    }
/**
 * Get the value of a checkbox from a web page<br/>
 * The value is true if checked, otherwise it is false
 * 
 * @param  string name The name of the checkbox to be checked
 * @param  string value The value of the checkbox to be checked
 * @return bool   This method passes the value gotten to the validateValue method and returns its output.
 */
    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            xarVar::fetch($name, 'isset', $value,  NULL, xarVar::NOT_REQUIRED);
        }
        return $this->validateValue($value);
    }
/**
 * Validate the value of a checkbox (checked or not checked)
 *
 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
 */
    public function validateValue($value = null)
    {
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name, xarLog::LEVEL_INFO);

        if (!isset($value)) $value = '';
        $this->setValue($value);
        return true;
    }
/**
 * Display a checkbox for input
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for input on a web page
 */
	
    public function showInput(Array $data = array())
    {
        if (isset($data['value'])) {
            if (is_array($data['value'])) {
                $this->value = implode(',',$data['value']);
            } else {
                $this->value = $data['value'];
            }
        }
        $data['value'] = $this->getValue();
        if (!isset($data['rows_cols'])) $data['rows_cols'] = $this->display_columns;
        return parent::showInput($data);
    }
/**
 * Display a checkbox for output
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for output on a web page
 */
    public function showOutput(Array $data = array())
    {
        if (isset($data['value'])) $this->value = $data['value'];
        $data['value'] = $this->getValue();
        if (isset($data['options']))  $this->options = $data['options'];
        $data['options'] = $this->getOptions();
        return parent::showOutput($data);
    }
/**
 * Used to show the hidden data
 * 
 * @param  array data An array of input parameters
 * @return bool   Returns true or false 
 */	  
    public function showHidden(Array $data = array())
    {
        if (isset($data['value'])) {
            if (is_array($data['value'])) {
                $data['value'] = implode(',',$data['value']);
            }
        } else {
            $data['value'] = '';
        }
        return parent::showHidden($data);
    }
/**
 * Get the value of input
 *  Check the value of input whether it is in an array or not.
 * If value of input is not an array it converts string into array first.
 * 
 * @return array    return always array value
 */	 
    public function getValue()
    {
        if (!is_array($this->value)) {
            if (is_string($this->value) && !empty($this->value)) {
                $value = explode(',', $this->value);
            } else {
                $value = array();
            }
        } else {
            $value = $this->value;
        }
        return $value;
    }
/**
 * Set the value of input
 * 
 * @param  string value The value of the input
 * @return string    return a storable representation of a value
 */	   
    public function setValue($value=null)
    {
        if ( is_array($value) ) $this->value = implode ( ',', $value);
        else $this->value = $value;
    }
}

?>
