<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textarea');

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This property holds the configuration of a dataproperty
 */
class ConfigurationProperty extends TextAreaProperty
{
    public $id         = 998;
    public $name       = 'configuration';
    public $desc       = 'Configuration';
    public $reqmodules = array('dynamicdata');

    // Default to static text
    public $proptype = 1;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->template   = 'configuration';
        $this->include_reference = 1;
    }

	/**
	* Get the value of a textarea from a web page
	* 
	* @param  string name The name of the textarea
	* @param  string value The value of the textarea
	* @return bool   This method returns true if $data configuration updated otherwise returns false 
	*/	
    public function checkInput($name = '', $value = null)
    {
        // set property type from object reference (= dynamic configuration) if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['property_id'])) {
            $this->proptype = $this->objectref->properties['property_id']->value;
        }
        $data['type'] = $this->proptype;

        if (empty($data['type'])) {
            $data['type'] = 1; // default DataProperty class
        }

        $data['name'] = !empty($name) ? $name : $this->propertyprefix . $this->id;
        $property =& DataPropertyMaster::getProperty($data);
        if (empty($property)) return;

        if (!xarVar::fetch($data['name'],'isset',$data['configuration'],NULL,xarVar::NOT_REQUIRED)) return;

        if (!$property->updateConfiguration($data)) return false;
        $this->value = $property->configuration;

        return true;
    }

	/**
	* Display a textarea for input
	* 
	* @param  array data An array of input parameters
	* @return string     HTML markup to display the property for input on a web page
	*/	
    public function showInput(Array $data = array())
    {
        // set property type from object reference (= dynamic configuration) if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['property_id'])) {
            $this->proptype = $this->objectref->properties['property_id']->value;
            $data['type'] = $this->proptype;
        }

        // Override from input
        if (!empty($data['type'])) {
            $this->proptype = $data['type'];
        } else {
            $data['type'] = $this->proptype;
        }
        
        $property =& DataPropertyMaster::getProperty($data);
        $property->id = $this->id;
        $property->parseConfiguration($this->value);

        // call its showConfiguration() method and return
        return $property->showConfiguration($data);
    }

	/**
	* Display a textarea for output
	* 
	* @param  array data An array of input parameters
	* @return string     HTML markup to display the property for output on a web page
	*/	
    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        return parent::showOutput($data);
    }

    /**
     * Export the serialized value as is here
     */
    public function exportValue($itemid, $item)
    {
        // return xarVar::prepForDisplay($item[$this->name]);
        if (isset($item[$this->name]) && is_array($item[$this->name])) {
            return serialize($item[$this->name]);
        }
        // don't replace anything in the serialized value
        return $item[$this->name];
    }
}
