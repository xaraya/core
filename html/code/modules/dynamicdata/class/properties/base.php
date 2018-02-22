<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

sys::import('modules.dynamicdata.class.properties.master');
sys::import('modules.dynamicdata.class.properties.interfaces');

/**
 * Base Class for Dynamic Properties
 *
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
    public $translatable   = 0;          // as it says
    public $status         = 33;
    public $seq            = 0;
    public $format         = '0'; //<-- eh?
    public $filepath       = 'auto';
    public $class          = '';         // this property's class

    // Attributes for runtime
    public $descriptor;                  // the description object of this property
    public $template = '';
    public $layout = '';
    public $tplmodule = 'dynamicdata';
    public $configuration = 'a:0:{}';
    public $dependancies = '';           // semi-colon seperated list of files that must be present for this property to be available (optional)
    public $args         = array();      //args that hold alias info
    public $anonymous = 0;               // if true the name, rather than the dd_xx designation is used in displaying the property

    public $datastore = '';              // name of the data store where this property comes from

    public $value          = null;       // value of this property for a particular DataObject
    public $previous_value = null;       // previous value of this property (if supported)
    public $filter         = 'nofilter'; // value of the filter of this property (if it is part of a filter layout)
    public $invalid        = '';         // result of the checkInput/validateValue methods
    public $basetype       = 'string';   // the primitive data type of this property

    public $include_reference = 0; // tells the object this property belongs to whether to add a reference of itself to me
    public $objectref = null;      // object this property belongs to
    public $_objectid = null;      // objectid this property belongs to
    public $_fieldprefix = '';     // the object's fieldprefix
    public $propertyprefix = 'dd_';// the object's fieldprefix

    public $_itemid;               // reference to $itemid in DataObject, where the current itemid is kept
    public $_items;                // reference to $items in DataObjectList, where the different item values are kept

    public $configurationtypes = array('display','validation','initialization');
//    public $display_template                = "";
    public $display_layout                  = "default";      // we display the default layout of a template
    public $display_required                = false;          // the field is not tagged as "required" for input
    public $display_tooltip                 = "";             // there is no tooltip text, and so no tooltip
    public $display_striptags               = false;          // we don't filter out certain HTML tags
    public $initialization_encrypt          = false;          // if the value is stored in encrypted form, the db field needs to be varchar, text etc.
    public $initialization_transform        = false;          // generic trigger that can be checked in getValue and setValue
    public $initialization_other_rule       = null;
    public $validation_notequals            = null;           //  check whether a property value does not equal a given value
    public $validation_equals               = null;           //  check whether a property value equals a given value
    public $validation_allowempty           = null;           // 

    /**
     * Default constructor setting the variables
     */
    public function __construct(ObjectDescriptor $descriptor)
    {
        // Set the default status for properties
        $this->status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE + DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
        
        $this->descriptor = $descriptor;
        $args = $descriptor->getArgs();
        $this->template = $this->getTemplate();

        $descriptor->refresh($this);
        // load the configuration, if one exists
        if (!empty($this->configuration) && ($this->configuration != 'a:0:{}')) {
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
            // The try clause is to gracefully exit in those cases where we are just importing properties
            // but don't yet have the full configuration
            try {
                $this->setValue($this->defaultvalue);
            } catch (Exception $e) {}
        } else {
            $this->setValue($args['value']);
        }
        // do the minimum for alias info, let the single property do the rest
        if (!empty($this->args)) {
            try {
                $this->args = unserialize($this->args);
            } catch (Exception $e) {}
        }
    }

    /**
     * Return the label of this property as per its descriptor
     */
    public function getLabel()
    {
        $label = $this->descriptor->get('label');
        return $label;
    }

    /**
     * Return the datasource of this property as per its descriptor
     */
    public function getSource($format='full')
    {
        $source = $this->descriptor->get('source');
        if ($format == 'field') {
            $parts = explode('.', $source);
            if (isset($parts[1])) $source = $parts[1];
        }
        return $source;
    }

    /**
     * Set the datasource of this property to a given value, or to its original value
     */
    public function setSource($source='')
    {
        if (empty($source)) $source = $this->descriptor->get('source');
        $this->source = $source;
        return true;
    }

    /**
     * Find the datastore name and type corresponding to the data source of a property
     */
    function getDataStore()
    {
        // Get the module name if we are looking at modvar storage
        $nameparts = explode(': ', $this->source);
        if (isset($nameparts[1])) {
            $modvarmodule = $nameparts[1];
            $source = 'module variable';
        } else {
            $source = $this->source;
        }
        switch($source) {
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
            case 'module variable':
                // data available in module variables
                // we'll keep a separate data store per module/itemtype here for now
                // TODO: (don't) integrate module variable handling with DD
                $storename = $modvarmodule . '__' . $this->name;
                $storetype = 'modulevars';
                break;
            case 'none':
                // no data storage
                $storename = '_none_';
                $storetype = 'none';
                break;
            default:
                // Nothing specific, perhaps a table?
                if(preg_match('/^(.+)\.(\w+)$/', $source, $matches))
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

    public function clearValue()
    {
        $this->value = null;
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
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        $this->invalid = '';
        if(!isset($value)) {
            list($found,$value) = $this->fetchValue($name);
            if (!$found) {
                $this->objectref->missingfields[] = $this->name;
                return null;
            }
        }

        // Check for a filter option if found save it
//        list($found,$filter) = $this->fetchValue($name. '_filteroption');
//        if ($found) $this->filter = $filter;

        // Check for a previous if found save it
        list($found,$previous_value) = $this->fetchValue('previous_value_' . $name);
        if ($found) $this->previous_value = $previous_value;

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

        xarLog::message("DataProperty::validateValue: Validating property " . $this->name, xarLog::LEVEL_INFO);

        if ($this->validation_notequals != null && $value == $this->validation_notequals) {
            if (!empty($this->validation_notequals_invalid)) {
                $this->invalid = xarML($this->validation_notequals_invalid);
            } else {
                $this->invalid = xarML('#(1) cannot have the value #(2)', $this->name,$this->validation_notequals );
            }
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        } elseif ($this->validation_equals != null && $value != $this->validation_equals) {
            if (!empty($this->validation_equals_invalid)) {
                $this->invalid = xarML($this->validation_equals_invalid);
            } else {
                $this->invalid = xarML('#(1) must have the value #(2)', $this->name,$this->validation_notequals );
            }
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        } elseif ($this->validation_allowempty != null && !$this->validation_allowempty && empty($value)) {
            if (!empty($this->validation_allowempty_invalid)) {
                $this->invalid = xarML($this->validation_allowempty_invalid);
            } else {
                $this->invalid = xarML('#(1) cannot be empty', $this->name);
            }
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        }
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
     * @param integer fordisplay
     */
    function setItemValue($itemid, $value, $fordisplay=0)
    {
        $this->value = $value;
        switch ($fordisplay) {
            case 0:
                $this->_items[$itemid][$this->name] = $this->value;
            break;
            case 1:
                $this->_items[$itemid][$this->name] = $this->getValue();
            break;
            case 2:
                $this->_items[$itemid][$this->label] = $this->value;
            break;
            case 3:
                $this->_items[$itemid][$this->label] = $this->getValue();
            break;
        }
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
        $this->status = $status + $this->getDisplayStatus();
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
     * 
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
        if ($this->anonymous == true) $name = $this->name;
        else $name = $this->propertyprefix . $this->id;
        $id = $name;

        // Add the object's field prefix if there is one
        $prefix = '';
        // Allow 0 as a fieldprefix
        if(!empty($this->_fieldprefix) || $this->_fieldprefix === 0)  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($data['fieldprefix']))  $prefix = $data['fieldprefix'] . '_';
        if(!empty($prefix)) $name = $prefix . $name;
        if(!empty($prefix)) $id = $prefix . $id;

        // Allow for overrides form the template
        if(!isset($data['id']))          $data['id']   = $id;
        if(!isset($data['name']))        $data['name']   = $name;

        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template']))    $data['template'] = $this->template;
        if(!isset($data['layout']))      $data['layout']   = $this->display_layout;

        if(!isset($data['tabindex']))    $data['tabindex'] = 0;
        if(!isset($data['value']))       $data['value']    = $this->value;
        if (!empty($this->invalid)) {
            $data['invalid']  = !empty($data['invalid']) ? $data['invalid'] : xarML($this->invalid);
        } else {
            $data['invalid']  = '';
        }

        // Add the configuration options defined via UI
        if(isset($data['configuration'])) {
            $this->parseConfiguration($data['configuration']);
            unset($data['configuration']);
        }
        // Now check for overrides from the template
        foreach ($this->configurationtypes as $configtype) {
            $properties = $this->getConfigProperties($configtype,1);
            foreach ($properties as $name => $configarg) {
                if (!isset($data[$configarg['shortname']]))
                    $data[$configarg['shortname']] = $this->{$configarg['fullname']};
            }
        }
        return xarTpl::property($data['tplmodule'], $data['template'], 'showinput', $data);
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
        if (empty($data['_itemid'])) $data['_itemid'] = 0;

        if(!isset($data['value']))     $data['value']    = $this->value;

        // If we are set up to do so, translate this value
        if ($this->translatable && xarMod::isAvailable('translations')) {
            xarMLS::_loadTranslations(xarMLS::DNTYPE_OBJECT, 'object', 'objects:' . $this->objectref->name, $this->name);
            $data['value'] = xarML($data['value']);
        }
        
        // If this is set, pass only allowed HTML tags
        if ($this->display_striptags)  $data['value']    = xarVarPrepHTMLDisplay($data['value']);
        
        // TODO: does this hurt when it is an array?
        if(!isset($data['tplmodule'])) $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template']))  $data['template'] = $this->template;
        if(!isset($data['layout']))    $data['layout']   = $this->display_layout;

        // Add the configuration options defined via UI
        if(isset($data['configuration'])) {
            $this->parseConfiguration($data['configuration']);
            unset($data['configuration']);
        }
        // Now check for overrides from the template
        foreach ($this->configurationtypes as $configtype) {
            $properties = $this->getConfigProperties($configtype,1);
            foreach ($properties as $name => $configarg) {
                if (!isset($data[$configarg['shortname']]))
                    $data[$configarg['shortname']] = $this->{$configarg['fullname']};
            }
        }
        return xarTpl::property($data['tplmodule'], $data['template'], 'showoutput', $data);
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

        $data['name']  = $this->name;
        $data['name']     = !empty($data['name']) ? $data['name'] : $this->propertyprefix . $this->id;
        $data['id']       = !empty($data['id'])   ? $data['id']   : $this->propertyprefix . $this->id;
        if(!isset($data['id'])) $data['id']   = $data['name'];
        
        $data['label'] = isset($data['label']) ? xarVarPrepForDisplay($data['label']) : xarVarPrepForDisplay($this->label);
        // Allow 0 as a fieldprefix
        if(!empty($this->_fieldprefix) || $this->_fieldprefix === '0' || $this->_fieldprefix === 0)  $data['fieldprefix'] = $this->_fieldprefix;
        // A field prefix added here can override the previous one
        if(isset($data['fieldprefix']))  $prefix = $data['fieldprefix'] . '_';
        if(!empty($prefix)) $data['name'] = $prefix . $data['name'];
        if(!empty($prefix)) $data['id'] = $prefix . $data['id'];
        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;
        if(!isset($data['title']))   $data['title']   = $this->display_tooltip;
        return xarTpl::property($data['tplmodule'], $data['template'], 'label', $data);
    }

    /**
     * Show the filter options for this property
     *
     * @param $data['filters'] an array of filter options for the property 
     * @param $data['for'] label id to use for this property (id, name or nothing)
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showFilter(Array $data=array())
    {
        // A filter cannot be hidden or disables
        if($this->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN) return "";
        if($this->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) return "";
        
        // Make sure we can enter a value here
        $this->setInputStatus(DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY);
        
        $data['id']    = $this->id;
        $data['name']  = $this->name;
        
        // This is the array of all possible filter options
        $filteroptions = array(
                            '=' => array('id' => 'eq', 'name' => xarML('equals')),
                            '!=' => array('id' => 'ne', 'name' => xarML('not equals')),
                            '>' => array('id' => 'gt', 'name' => xarML('greater than')),
                            '>=' => array('id' => 'ge', 'name' => xarML('greater than or equal')),
                            '<' => array('id' => 'lt', 'name' => xarML('less than')),
                            '<=' => array('id' => 'le', 'name' => xarML('less than or equal')),
                            'like' => array('id' => 'like', 'name' => xarML('like')),
                            'notlike' => array('id' => 'notlike', 'name' => xarML('not like')),
                            'null' => array('id' => 'null', 'name' => xarML('is null')),
                            'notnull' => array('id' => 'notnull', 'name' => xarML('is not null')),
                            'regex' => array('id' => 'regex', 'name' => xarML('regular expression')),
                        );

        $data['filters'] = isset($data['filters']) ? $data['filters'] : array();
        
        // Explicitly cater to the most common basetypes so as to avoid duplication in the extensions
        $numbertypes = array('number','decimal','integer','float');
        $stringtypes = array('string');
        if (in_array($this->basetype, $numbertypes)) $data['filters'] = array('=','!=','>','>=','<','<=','like','notlike','null','notnull');
        elseif (in_array($this->basetype, $stringtypes)) $data['filters'] = array('like','notlike','=','!=','null','notnull','regex');
        elseif (in_array($this->basetype, array('dropdown'))) $data['filters'] = array('=');
        
        // Now create the filter options for the dropdown
        $data['options'] = array();
        foreach ($data['filters'] as $filter) $data['options'][] = $filteroptions[$filter];
        
        $data['value'] = isset($data['filter']) ? $data['filter'] : $this->filter;
        if(!empty($this->_fieldprefix) || $this->_fieldprefix === '0' || $this->_fieldprefix === 0)  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($data['fieldprefix']))  $prefix = $data['fieldprefix'] . '_';
        if(!empty($prefix)) $data['name'] = $prefix . $data['name'];
        if(!empty($prefix)) $data['id'] = $prefix . $data['id'];
        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;
        return xarTplProperty($data['tplmodule'], $data['template'], 'filter', $data);
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
        $data['name']     = !empty($data['name']) ? $data['name'] : $this->propertyprefix . $this->id;
        $data['id']       = !empty($data['id'])   ? $data['id']   : $this->propertyprefix . $this->id;

        // Add the object's field prefix if there is one
        $prefix = '';
        // Allow 0 as a fieldprefix
        if(!empty($this->_fieldprefix) || $this->_fieldprefix === '0' || $this->_fieldprefix === 0)  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($data['fieldprefix']))  $prefix = $data['fieldprefix'] . '_';
        if(!empty($prefix)) $data['name'] = $prefix . $data['name'];
        if(!empty($prefix)) $data['id'] = $prefix . $data['id'];

        $data['value']    = isset($data['value']) ? $data['value'] : $this->value;
        
        // The value might be an array
        if (is_array($data['value'])){
            $temp = array();
            foreach ($data['value'] as $key => $tmp) 
                $temp[$key] = (!is_array($tmp)) ? xarVarPrepForDisplay($tmp) : $tmp;
            $data['value'] = $temp;
        } else {
            $data['value'] = xarVarPrepForDisplay($data['value']);
        }

        $data['invalid']  = !empty($data['invalid']) ? $data['invalid'] : $this->invalid;
        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;

        return xarTpl::property($data['tplmodule'], $data['template'], 'showhidden', $data);
    }

    /**
     * For use in DD tags : preset="yes" - this can typically be used in admin-new.xt templates
     * for individual properties you'd like to automatically preset via GET or POST parameters
     *
     * Note: don't use this if you already check the input for the whole object or in the code
     * See also preview="yes", which can be used on the object level to preview the whole object
     *
     * @access private
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

        // fall back to the old N:M validation for text boxes et al. (cfr. utilapi_getstatic/getmeta)
        } elseif (preg_match('/^(\d+):(\d+)$/', $configuration, $matches)) {
            $fields = array('validation_min_length' => $matches[1],
                            'validation_max_length' => $matches[2],
                            'display_maxlength'     => $matches[2]);

        // try normal serialized configuration
        } else {
            try {
                $fields = unserialize($configuration);
            } catch (Exception $e) {
                // if the configuration is malformed just return an empty configuration
                $fields = array();
                return true;
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
        // Return the exploded fields
        return $fields;
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
        $fields = $this->parseConfiguration($data['configuration']);

        if (!isset($data['name']))  $data['name'] = $this->propertyprefix . $this->id;
        if (!isset($data['id']))  $data['id'] = $this->propertyprefix . $this->id;
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
        return xarTpl::property($data['module'], $data['template'], 'configuration', $data);
    }

    /**
     * Update the current configuration rule in a specific way for this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['configuration'] configuration rule (default is the current configuration)
     * @param $args['id'] id of the field
     * @return boolean true if the configuration rule could be processed, false otherwise
     */
    public function updateConfiguration(Array $data = array())
    {
        extract($data);
        $valid = false;
        // in case we need to process additional input fields based on the name
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;

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
        // cache configuration for all properties
        if (xarCoreCache::isCached('DynamicData','Configurations')) {
             $allconfigproperties = xarCoreCache::getCached('DynamicData','Configurations');
        } else {
            $xartable =& xarDB::getTables();
            $configurations = $xartable['dynamic_configurations'];

            $bindvars = array();
            $query = "SELECT id,
                             name,
                             description,
                             property_id,
                             label,
                             ignore_empty,
                             configuration
                      FROM $configurations ";

            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);

            $allconfigproperties = array();
            while ($result->next())
            {
                $item = $result->fields;
                $allconfigproperties[$item['name']] = $item;
            }
            xarCoreCache::setCached('DynamicData','Configurations', $allconfigproperties);
            // Can't use DD methods here as we go into a recursion loop
        }
        // if no items found, bail
        if (empty($allconfigproperties)) return $allconfigproperties;

        $configproperties = array();
        $properties = $this->getPublicProperties();
        foreach ($properties as $name => $arg) {
            // Ignore properties that are not defined as configs in the configurations table
            // and also those that are flagged as not to be active for this property object
            $flagname = $name . "_ignore";
            if (!isset($allconfigproperties[$name]) || !empty($this->$flagname)) continue;
            // Ignore properties that are not of the config $type passed
            $pos = strpos($name, "_");
            if (!$pos || (substr($name,0,$pos) != $type)) continue;
            // This one is good. Make an entry for it
            $key = $fullname ? $name : substr($name,$pos+1);
            $configproperties[$name] = $allconfigproperties[$name];
            $configproperties[$key]['value'] = $arg;
            $configproperties[$key]['shortname'] = substr($name,$pos+1);
            $configproperties[$key]['fullname'] = $name;
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

    protected function getCanonicalName($data=null)
    {
        if(!isset($data['name'])) {
            if ($this->anonymous == true) $data['name'] = $this->name;
            else $data['name'] = $this->propertyprefix . $this->id;
        }
        $data['name'] = $this->getPrefix($data) . $data['name'];
        return $data['name'];
    }

    protected function getCanonicalID($data=null)
    {
        if(!isset($data['id'])) $data['id']   = $this->getCanonicalName($data);
        $data['id'] = $this->getPrefix($data) . $data['id'];
        return $data['id'];
    }

    private function getPrefix($data=null)
    {
        // Add the object's field prefix if there is one
        $prefix = '';
        // Allow 0 as a fieldprefix
        if(!empty($this->_fieldprefix) || $this->_fieldprefix === 0)  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($data['fieldprefix']))  $prefix = $data['fieldprefix'] . '_';
        return $prefix;
    }

    public function addToObject($data=array())
    {
        return true;
    }
    public function removeFromObject($data=array())
    {
        return true;
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

    public function castType($value=null)
    {
        return (string)$value;
    }

    public function importValue(SimpleXMLElement $element)
    {
        return $this->castType((string)$element->{$this->name});
    }

    public function exportValue($itemid, $item)
    {
        return xarVarPrepForDisplay($item[$this->name]);
    }
    
    public function preCreate() { return true; }
    public function preUpdate() { return true; }
    public function preDelete() { return true; }
    public function preGet()    { return true; }
    public function preList()   { return true; }
}
?>