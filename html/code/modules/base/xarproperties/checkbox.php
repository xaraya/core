<?php
/* Include the parent class  */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * The Checkbox property models an HTML input of type checkbox
 * 
 * @package modules\base
 * @subpackage base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * This property displays a checkbox
 */
class CheckboxProperty extends DataProperty
{
    public $id         = 14;
    public $name       = 'checkbox';
    public $desc       = 'Checkbox';
    public $reqmodules = array('base');

    public $basetype   = 'checkbox';

/**
 * Create an instance of this dataproperty<br/>
 * - It belongs to the base module<br/>
 * - It has its own input/output templates<br/>
 * - it is found at modules/base/xarproperties<br/>
 *
 */
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'checkbox';
        $this->filepath  = 'modules/base/xarproperties';
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
            if (!xarVar::fetch($name, 'isset', $value,  NULL, xarVar::DONT_SET)) {return;}
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
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name, xarLog::LEVEL_DEBUG);

        if (empty($value) || $value == 'false') {
            $this->value = 0;
        } else {
            $this->value = 1;
        }
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
        if (isset($data['checked'])) $data['value']  = $data['checked'];
        if (!isset($data['value'])) $data['value'] = $this->value;
        if ($data['value'] === true || $data['value'] === 'true') $data['value'] = 1;
        elseif ($data['value'] === false || $data['value'] === 'false') $data['value'] = 0;
        $data['checked'] = $data['value'];
        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        return parent::showInput($data);
    }

/**
 * Convert an integer or string value to true/false
 * 
 * @param  mixed value The value to be converted
 * @return bool  Returns true if the integer or string value is 1, "1" or "true"; otherwise returns false.
 */
    public function castType($value=null)
    {
        return ($value === 1 || $value === '1' || $value === true || $value === 'true') ? true : false;
    }
}
?>
