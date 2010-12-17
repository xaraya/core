<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle the configuration property
 */
class ThemeConfigurationProperty extends TextBoxProperty
{
    public $id         = 30107;
    public $name       = 'themeconfiguration';
    public $desc       = 'Theme Configuration';
    public $reqmodules = array('themes');

    // Default to static text
    public $proptype = 1;
    //public $initialization_prop_type = null;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/themes/xarproperties';
        // Make sure we get an object reference so we can get the theme ID value
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
        sys::import('xaraya.structures.query');
        $tables = xarDB::getTables();
        $q = new Query('SELECT',$tables['themes_configurations']);
        $q->eq('theme_id',$themeid);
    //    $q->qecho();
        $q->run();
        $result = $q->output();
    //    var_dump($result);//exit;
    
        //$data['type'] = $data['value']['initialization_prop_type']; only shows once

        // set theme regid the from object reference (= theme_configuration) if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['theme_id'])) {
            $this->theme_id = $this->objectref->properties['theme_id']->value;
            $data['theme_id'] = $this->theme_id;
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