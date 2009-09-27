<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
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

    public $proptype = null;
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
        if (empty($this->proptype) && !empty($this->objectref) && !empty($this->objectref->properties['property_id'])) {
            $this->proptype = $this->objectref->properties['property_id']->value;
            $data['type'] = $this->proptype;
        }

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
        // set property type from input
        if (!empty($data['type'])) {
            $this->proptype = $data['type'];
        } else {
            $data['type'] = $this->proptype;
        }
        // set property type from object reference (= dynamic configuration) if possible
        if (empty($this->proptype) && !empty($this->objectref) && !empty($this->objectref->properties['property_id'])) {
            $this->proptype = $this->objectref->properties['property_id']->value;
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