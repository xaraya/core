<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

sys::import('modules.dynamicdata.class.properties.master');
sys::import('modules.dynamicdata.class.properties.interfaces');

/**
 * Base Class for Dynamic Properties
 *
 * @todo is this abstract?
 * @todo the visibility of most of the attributes can probably be protected
 */
class DataProperty extends Object implements iDataProperty
{
    // Attributes for registration
    public $id             = 0;
    public $name           = 'propertyName';
    public $desc           = 'propertyDescription';
    public $label          = 'Property Label';
    public $type           = 1;
    public $defaultvalue   = '';
    public $source         = 'dynamic_data';
    public $status         = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
    public $seq            = 0;
    public $format         = '0'; //<-- eh?
    public $filepath       = 'modules/dynamicdata/xarproperties';
    public $class          = '';         // this property's class

    // Attributes for runtime
    public $template = '';
    public $layout = '';
    public $tplmodule = 'dynamicdata';
    public $configuration = '';
    public $dependancies = '';    // semi-colon seperated list of files that must be present for this property to be available (optional)
    public $args         = array(); //args that hold alias info
    public $anonymous = 0;        // if true the name, rather than the dd_xx designation is used in displaying the property

    public $datastore = '';    // name of the data store where this property comes from

    public $value = null;      // value of this property for a particular DataObject
    public $invalid = '';      // result of the checkInput/validateValue methods

    public $include_reference = 0; // tells the object this property belongs whether to add a reference of itself to me
    public $objectref = null;  // object this property belongs to
    public $_objectid = null;  // objectid this property belongs to
    public $_fieldprefix = ''; // the object's fieldprefix

    public $_itemid;           // reference to $itemid in DataObject, where the current itemid is kept
    public $_items;            // reference to $items in DataObjectList, where the different item values are kept

    public $configurationtypes = array('display','validation','initialization');
//    public $display_template                = "";
    public $display_layout                  = "default";       // we display the default layout of a template
    public $display_required                = false;           // the field is not tagged as "required" for input
    public $display_tooltip                 = "";              // there is no tooltip text, and so no tooltip
    public $initialization_other_rule       = null;

    /**
     * Default constructor setting the variables
     */
    public function __construct(ObjectDescriptor $descriptor)
    {
        $this->descriptor = $descriptor;
        $args = $descriptor->getArgs();
        $this->template = $this->getTemplate();

        $descriptor->refresh($this);
        // load the configuration, if one exists
        if (!empty($this->configuration)) {
            $this->parseConfiguration($this->configuration);
        }

        if(!isset($args['value'])) {
            // if the default field looks like <something>(...), we'll assume that this
            // a function call that returns some dynamic default value
            // Expression stolen from http://php.net/functions
            if(!empty($this->defaultvalue) && preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\(.*\)/',$this->defaultvalue)) {
                eval('$value = ' . $this->defaultvalue .';');
                if(isset($value)) {
                    $this->defaultvalue = $value;
                } else {
                    $this->defaultvalue = null;
                }
            }
            $this->value = $this->defaultvalue;
        }
        // do the minimum for alias info, let the single property do the rest
        if (!empty($this->args)) {
            try {
                $this->args = unserialize($this->args);
            } catch (Exception $e) {}
        }
    }

    /**
     * Find the datastore name and type corresponding to the data source of a property
     */
    function getDataStore()
    {
        switch($this->source) {
            case 'dynamic_data':
                // Variable table storage method, aka 'usual dd'
                $storename = '_dynamic_data_';
                $storetype = 'data';
                break;
            case 'hook module':
                // data managed by a hook/utility module
                $storename = '_hooks_';
                $storetype = 'hook';
                break;
            case 'user function':
                // data managed by some user function (specified in configuration for now)
                $storename = '_functions_';
                $storetype = 'function';
                break;
            case 'user settings':
                // data available in user variables
                // we'll keep a separate data store per module/itemtype here for now
                // TODO: (don't) integrate user variable handling with DD
                $storename = 'uservars_'.$this->_objectid.'_'.$this->type;
                $storetype = 'uservars';
                break;
            case 'module variables':
                // data available in module variables
                // we'll keep a separate data store per module/itemtype here for now
                // TODO: (don't) integrate module variable handling with DD
                $storename = 'module variables_'.$this->name;
                $storetype = 'modulevars';
                break;
            case 'dummy':
                // no data storage
                $storename = '_dummy_';
                $storetype = 'dummy';
                break;
            default:
                // Nothing specific, perhaps a table?
                if(preg_match('/^(.+)\.(\w+)$/', $this->source, $matches))
                {
                    // data field coming from some static table : [database.]table.field
                    $table = $matches[1];
                    $field = $matches[2];
                    $storename = $table;
                    $storetype = 'table';
                    break;
                }
                // Must be on the todo list then.
                // TODO: extend with LDAP, file, ...
                $storename = '_todo_';
                $storetype = 'todo';
        }
        return array($storename, $storetype);
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * @return mixed the value for the property
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param mixed $value the new value for the property
     */
    public function setValue($value=null)
    {
        $this->value = $value;
    }

    /**
     * Fetch the input value of this property
     *
     * @param string $name name of the input field
     * @return array an array containing a flag whether the value was found and the found value itself
     */
    public function fetchValue($name = '')
    {
        $found = false;
        $value = null;
        xarVarFetch($name, 'isset', $namevalue, NULL, XARVAR_DONT_SET);
        if(isset($namevalue)) {
            $found = true;
            $value = $namevalue;
        }
        return array($found,$value);
    }

    /**
     * Check the input value of this property
     *
     * @param string $name name of the input field (default is 'dd_NN' with NN the property id)
     * @param mixed  $value value of the input field (default is retrieved via xarVarFetch())
     */
    public function checkInput($name = '', $value = null)
    {
            // store the fieldname for configurations who need them (e.g. file uploads)
        $name = empty($name) ? 'dd_'.$this->id : $name;
        $this->fieldname = $name;
        $this->invalid = '';
        if(!isset($value)) {
            list($found,$value) = $this->fetchValue($name);
            if (!$found) {
            // store the fieldname for configurations who need them (e.g. file uploads)
                $this->objectref->missingfields[] = $this->name;
                return null;
            }
        }
       return $this->validateValue($value);
    }

    /**
     * Validate the value of this property
     *
     * @param mixed $value value of the property (default is the current value)
     */
    public function validateValue($value = null)
    {
        if(!isset($value)) $value = $this->getValue();
        else $this->setValue($value);

//        $this->value = null;
//        $this->invalid = xarML('unknown property');
//        return false;
        return true;
    }

    /**
     * Get the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid the item id we want the value for
     * @return mixed
     */
    function getItemValue($itemid)
    {
        return $this->_items[$itemid][$this->name];
    }

    /**
     * Set the value of this property for a particular item (= for object lists)
     *
     * @param int $itemid
     * @param mixed value
     */
    function setItemValue($itemid, $value)
    {
        $this->_items[$itemid][$this->name] = $value;
    }

    /**
     * Get and set the value of this property's display status
     */
    function getDisplayStatus()
    {
        return ($this->status & DataPropertyMaster::DD_DISPLAYMASK);
    }
    function setDisplayStatus($status)
    {
        $this->status = $status & DataPropertyMaster::DD_DISPLAYMASK;
    }

    /**
     * Get and set the value of this property's input status
     */
    function getInputStatus()
    {
        return $this->status - $this->getDisplayStatus();
    }
    function setInputStatus($status)
    {
        $this->status = $status - $this->getDisplayStatus();
    }

    /**
     * Show an input field for setting/modifying the value of this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @param $args['module'] which module is responsible for the templating
     * @param $args['template'] what's the partial name of the showinput template.
     * @param $args[*] rest of arguments is passed on to the templating method.
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showInput(Array $data = array())
    {
        if (!empty($data['hidden'])) {
            if ($data['hidden'] == 'active') {
                $this->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE);
            } elseif ($data['hidden'] == 'display') {
                $this->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY);
            } elseif ($data['hidden'] == 'hidden') {
                $this->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN);
            }
        }

        if($this->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            return $this->showHidden($data);

        if($this->getInputStatus() == DataPropertyMaster::DD_INPUTSTATE_NOINPUT) {
            return $this->showOutput($data) . $this->showHidden($data);
        }

        // Display directive for the name
        if(!isset($data['name'])) {
            if ($this->anonymous == true) $data['name'] = $this->name;
            else $data['name'] = 'dd_'.$this->id;
        }
        if(!isset($data['id']))          $data['id']   = $data['name'];

        // Add the object's field prefix if there is one
        $prefix = '';
        if(!empty($this->_fieldprefix))  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($data['fieldprefix']))  $prefix = $data['fieldprefix'] . '_';
        if(!empty($prefix)) $data['name'] = $prefix . $data['name'];
        if(!empty($prefix)) $data['id'] = $prefix . $data['id'];

        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->display_layout;

        if(!isset($data['tabindex'])) $data['tabindex'] = 0;
        if(!isset($data['value']))    $data['value']    = '';
        $data['invalid']  = !empty($this->invalid) ? xarML($this->invalid) :'';

        // Add the configuration options if they have not been overridden
        if(isset($data['configuration'])) {
            $this->parseConfiguration($data['configuration']);
            unset($data['configuration']);
        }
        foreach ($this->configurationtypes as $configtype) {
            $properties = $this->getConfigProperties($configtype,1);
            foreach ($properties as $name => $configarg) {
                if (!isset($data[$configarg['shortname']]))
                    $data[$configarg['shortname']] = $this->{$configarg['fullname']};
            }
        }
        return xarTplProperty($data['tplmodule'], $data['template'], 'showinput', $data);
    }

    /**
     * Show some default output for this property
     *
     * @param $args['value'] value of the property (default is the current value)
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showOutput(Array $data = array())
    {
        if (!empty($data['hidden'])) {
            if ($data['hidden'] == 'active') {
                $this->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE);
            } elseif ($data['hidden'] == 'display') {
                $this->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY);
            } elseif ($data['hidden'] == 'hidden') {
                $this->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN);
            }
        }

        if($this->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            return $this->showHidden($data);

        $data['id']   = $this->id;
        $data['name'] = $this->name;

        if(!isset($data['value'])) $data['value'] = $this->getValue();
        // TODO: does this hurt when it is an array?
        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->display_layout;

        return xarTplProperty($data['tplmodule'], $data['template'], 'showoutput', $data);
    }

    /**
     * Show the label for this property
     *
     * @param $data['label'] label of the property (default is the current label)
     * @param $data['for'] label id to use for this property (id, name or nothing)
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showLabel(Array $data=array())
    {
        if($this->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            return "";

        if(empty($data))
        {
            // old syntax was showLabel($label = null)
        }
        elseif(is_string($data))
            $label = $data;
        elseif(is_array($data))
            extract($data);

        $data['id']    = $this->id;
        $data['name']  = $this->name;
        $data['label'] = isset($label) ? xarVarPrepForDisplay($label) : xarVarPrepForDisplay($this->label);
        $data['for']   = isset($for) ? $for : null;
        if(!empty($this->_fieldprefix))  $data['fieldprefix'] = $this->_fieldprefix;
        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;
        return xarTplProperty($data['tplmodule'], $data['template'], 'label', $data);
    }

    /**
     * Show a hidden field for this property
     *
     * @param $data['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $data['value'] value of the field (default is the current value)
     * @param $data['id'] id of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showHidden(Array $data = array())
    {
        $data['name']     = !empty($data['name']) ? $data['name'] : 'dd_'.$this->id;
        $data['id']       = !empty($data['id'])   ? $data['id']   : 'dd_'.$this->id;

        // Add the object's field prefix if there is one
        $prefix = '';
        if(!empty($this->_fieldprefix))  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($data['fieldprefix']))  $prefix = $data['fieldprefix'] . '_';
        if(!empty($prefix)) $data['name'] = $prefix . $data['name'];
        if(!empty($prefix)) $data['id'] = $prefix . $data['id'];

        $data['value']    = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->getValue());
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;

        return xarTplProperty($data['tplmodule'], $data['template'], 'showhidden', $data);
    }

    /**
     * For use in DD tags : preset="yes" - this can typically be used in admin-new.xd templates
     * for individual properties you'd like to automatically preset via GET or POST parameters
     *
     * Note: don't use this if you already check the input for the whole object or in the code
     * See also preview="yes", which can be used on the object level to preview the whole object
     *
     * @access private (= do not sub-class)
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public final function _showPreset(Array $data = array())
    {
        if(empty($data['name'])) $isvalid = $this->checkInput();
        else $isvalid = $this->checkInput($data['name']);
        if(!$isvalid) $isvalid = $this->checkInput($this->name);

        if(!empty($data['hidden'])) return $this->showHidden($data);
        else return $this->showInput($data);
    }

    /**
     * Parse the configuration rule
     *
     * @param string $configuration
     */
    public function parseConfiguration($configuration = '')
    {
        if (is_array($configuration)) {
            $fields = $configuration;
        } elseif (empty($configuration)) {
            return true;
        } else {
            try {
                $fields = unserialize($configuration);
            } catch (Exception $e) {
                // if the configuration is malformed just return an empty configuration
                $fields = array();
            }
        }
        if (!empty($fields) && is_array($fields)) {
            foreach ($this->configurationtypes as $configtype) {
                $properties = $this->getConfigProperties($configtype,1);
                foreach ($properties as $name => $configarg) {
                    if (isset($fields[$name])) {
                        $this->$name = $fields[$name];
                    }
                    $msgname = $name . '_invalid';
                    if (isset($fields[$msgname])) {
                        $this->$msgname = $fields[$msgname];
                    }
                }
            }
        }
    }

    /**
     * The following methods provide an interface to show and update configuration rules
     * when editing dynamic properties. They should be customized for each property
     * type, based on its specific format and interpretation of the configuration rules.
     *
     * This allows property type developers to support more complex configuration rules,
     * while keeping them easy to modify for the site admins afterwards.
     *
     * If no configuration methods are specified for a particular property type, the
     * corresponding methods from its parent class will be used.
     *
     * Note: the methods can be called by DD's showpropval() function, or if you set the
     *       type of the 'configuration' property (21) to ConfigurationProperty also
     *       via DD's modify() and update() functions if you edit some dynamic property.
     */

    /**
     * Show the current configuration rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['configuration'] configuration rule (default is the current configuration)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showConfiguration(Array $data = array())
    {
        if (!isset($data['configuration'])) $data['configuration'] = $this->configuration;
        $this->parseConfiguration($data['configuration']);

        if (!isset($data['name']))  $data['name'] = 'dd_'.$this->id;
        if (!isset($data['id']))  $data['id'] = 'dd_'.$this->id;
        if (!isset($data['tabindex']))  $data['tabindex'] = 0;
        if (!isset($this->invalid))  $data['invalid'] = xarML('Invalid #(1)', $this->invalid);
        else $data['invalid'] = '';
        if (isset($data['required']) && $data['required']) $data['required'] = true;
        else $data['required'] = false;
        if(!isset($data['module']))   $data['module']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->display_layout;

        if (!isset($data['display'])) $data['display'] = $this->getConfigProperties('display',1);
        if (!isset($data['validation'])) $data['validation'] = $this->getConfigProperties('validation',1);
        if (!isset($data['initialization'])) $data['initialization'] = $this->getConfigProperties('initialization',1);

        // Collect the invalid messages for the validations
        foreach ($data['validation'] as $validationitem) {
            $msgname = $validationitem['name'] . '_invalid';
            if (isset($this->$msgname)) $data['validation'][$msgname] = $this->$msgname;
            else $data['validation'][$msgname] = '';
        }
        return xarTplProperty($data['module'], $data['template'], 'configuration', $data);
    }

    /**
     * Update the current configuration rule in a specific way for this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['configuration'] configuration rule (default is the current configuration)
     * @param $args['id'] id of the field
     * @return bool true if the configuration rule could be processed, false otherwise
     */
    public function updateConfiguration(Array $data = array())
    {
        extract($data);
        $valid = false;
        // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;

        // do something with the configuration and save it in $this->configuration
        if (isset($configuration) && is_array($configuration)) {
            $storableconfiguration = array();
            foreach ($this->configurationtypes as $configtype) {
                $properties = $this->getConfigProperties($configtype,1);
                foreach ($properties as $name => $configarg) {
                    if (isset($configuration[$name])) {
                        if ($configarg['ignore_empty'] && ($configuration[$name] == '')) continue;
                        $storableconfiguration[$name] = $configuration[$name];
                    }
                    // Invalid messages only get stored if they are non-empty. For all others we check whether they exist (for now)
                    $msgname = $name . '_invalid';
                    if (isset($configuration[$msgname]) && !empty($configuration[$msgname])) {
                        $storableconfiguration[$msgname] = $configuration[$msgname];
                    }
                }
            }
            $this->configuration = serialize($storableconfiguration);
            $valid = true;

        } else {
            $this->configuration = serialize(array());
            $valid = true;
        }
        return $valid;
    }

    /**
     * Deprecated methods
     */
    public function parseValidation($configuration='')  { return $this->parseConfiguration($configuration); }
    public function showValidation(Array $data = array())   { return $this->showConfiguration($data); }
    public function updateValidation(Array $data = array()) { return $this->updateConfiguration($data); }

    /**
     * Return the configuration options for this property
     *
     * @param $type:  type of option (display, initialization, validation)
     * @param $fullname: return the full name asa key, e.g. "display_size
     * @return array of configuration options
     */
    public function getConfigProperties($type="", $fullname=0)
    {
        static $allconfigproperties;

        if (empty($allconfigproperties)) {
            $xartable = xarDB::getTables();
            $q = new xarQuery('SELECT',$xartable['dynamic_configurations']);
            if (!$q->run()) return;
            $allconfigproperties = $q->output();

            // Use this if we have DD storage
//            sys::import('modules.query.class.ddquery');
//            $q = new DDQuery('configurations');
//            if (!$q->run()) return;
//            $allconfigproperties = $q->output();

            // Can't use DD methods here as we go into a recursion loop
//            $object = DataObjectMaster::getObjectList(array('name' => 'configurations'));
//            $allconfigproperties = $object->getItems();
        }
        $config = array();
        foreach ($allconfigproperties as $item) $config[$item['name']] = $item;
        // if no items found, bail
        if (empty($config)) return $config;

        $configproperties = array();
        $properties = $this->getPublicProperties();
        foreach ($properties as $name => $arg) {
            if (!isset($config[$name])) continue;
            $pos = strpos($name, "_");
            if (!$pos || (substr($name,0,$pos) != $type)) continue;
            if ($fullname) {
                $configproperties[$name] = $config[$name];
                $configproperties[$name]['value'] = $arg;
                $configproperties[$name]['shortname'] = substr($name,$pos+1);
                $configproperties[$name]['fullname'] = $name;
            } else {
                $configproperties[substr($name,$pos+1)] = $config[$name];
                $configproperties[substr($name,$pos+1)]['value'] = $arg;
                $configproperties[substr($name,$pos+1)]['shortname'] = substr($name,$pos+1);
                $configproperties[substr($name,$pos+1)]['fullname'] = $name;
            }
        }
        return $configproperties;
    }

    /**
     * Return the module this property belongs to
     *
     * @return string module name
     */
    protected function getModule()
    {
        $modulename = empty($this->tplmodule) ? $info['tplmodule'] : $this->tplmodule;
        return $modulename;
    }
    /**
     * Return the name this property uses in its templates
     *
     * @return string template name
     */
    protected function getTemplate()
    {
        // If not specified, default to the registered name of the prop
        $template = empty($this->template) ? $this->name : $this->template;
        return $template;
    }

    public static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = $this->reqmodules;
        $info->id   = $this->id;
        $info->name = $this->name;
        $info->desc = $this->desc;

        return $info;
    }

    function aliases()
    {
        return array();
    }
}
?>
