<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle the configuration property
 */
class ConfigurationProperty extends TextBoxProperty
{
    public $id         = 998;
    public $name       = 'configuration';
    public $desc       = 'Configuration';
    public $reqmodules = array('dynamicdata');

    // Default to static text
    public $proptype = 1;
    //public $initialization_prop_type = null;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->include_reference = 1;
    }

    public function checkInput($name = '', $value = null)
    {
        // set property type from object reference (= dynamic configuration) if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['property_id'])) {
            $this->proptype = $this->objectref->properties['property_id']->value;
        }
        $data['type'] = $this->proptype;

// TODO: support nested configurations (e.g. for array of properties) ?
//       Problem is setting the proptype of the child config in the parent config

        if (empty($data['type'])) {
            $data['type'] = 1; // default DataProperty class
        }

        $data['name'] = !empty($name) ? $name : 'dd_'.$this->id;
        $property =& DataPropertyMaster::getProperty($data);
        if (empty($property)) return;

        if (!xarVarFetch($data['name'],'isset',$data['configuration'],NULL,XARVAR_NOT_REQUIRED)) return;

        if (!$property->updateConfiguration($data)) return false;
        $this->value = $property->configuration;

        return true;
    }

    public function showInput(Array $data = array())
    {
        //$data['type'] = $data['value']['initialization_prop_type']; only shows once

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

    public function showOutput(Array $args = array())
    {
        extract($args);

        if (isset($value)) {
            $value = xarVarPrepHTMLDisplay($value);
        } else {
            $value = xarVarPrepHTMLDisplay($this->value);
        }

        return $value;
    }
}
?>