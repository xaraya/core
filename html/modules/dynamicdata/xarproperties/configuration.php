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

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->include_reference = 1;
    }

    public function checkInput($name = '', $value = null)
    {
        if (!isset($newtype)) {
            $newtype = $this->objectref->properties['property_id']->value;
        }

        // get a new property of the right type
        if (!empty($newtype)) {
            $data['type'] = $newtype;
        } elseif (!empty($proptype->value)) {
            $data['type'] = $proptype->value;
        } else {
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
        $data['type'] = $this->objectref->properties['property_id']->value;

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
