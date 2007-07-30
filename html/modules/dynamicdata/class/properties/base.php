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
    public $default        = '';
    public $source         = 'dynamic_data';
    public $status         = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
    public $order          = 0;
    public $format         = '0'; //<-- eh?
    public $filepath       = 'modules/dynamicdata/xarproperties';
    public $class          = '';         // what class is this?

    // Attributes for runtime
    public $template = '';
    public $layout = '';
    public $tplmodule = 'dynamicdata';
    public $validation = '';
    public $dependancies = '';    // semi-colon seperated list of files that must be present for this property to be available (optional)
    public $args         = array();

    public $datastore = '';   // name of the data store where this property comes from

    public $value = null;     // value of this property for a particular DataObject
    public $invalid = '';     // result of the checkInput/validateValue methods

    // public $objectref = null; // object this property belongs to
    public $_objectid = null; // objectid this property belongs to

    public $_itemid;          // reference to $itemid in DataObject, where the current itemid is kept
    public $_items;           // reference to $items in DataObjectList, where the different item values are kept

    /**
     * Default constructor setting the variables
     */
    public function __construct(ObjectDescriptor $descriptor)
    {
        $this->descriptor = $descriptor;
        $args = $descriptor->getArgs();
        $this->template = $this->getTemplate();
        $this->args = serialize(array());

        $descriptor->refresh($this);

        if(!isset($args['value'])) {
            // if the default field looks like <something>(...), we'll assume that this
            // a function call that returns some dynamic default value
            // Expression stolen from http://php.net/functions
            if(!empty($this->default) && preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\(.*\)/',$this->default)) {
                eval('$value = ' . $this->default .';');
                if(isset($value)) {
                    $this->default = $value;
                } else {
                    $this->default = null;
                }
            }
            $this->value = $this->default;
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
                // data managed by some user function (specified in validation for now)
                $storename = '_functions_';
                $storetype = 'function';
                break;
            case 'user settings':
                // data available in user variables
                // we'll keep a separate data store per module/itemtype here for now
                // TODO: (don't) integrate user variable handling with DD
                $storename = 'uservars_'.$this->moduleid.'_'.$this->itemtype; //FIXME change id
                $storetype = 'uservars';
                break;
            case 'module variables':
                // data available in module variables
                // we'll keep a separate data store per module/itemtype here for now
                // TODO: (don't) integrate module variable handling with DD
                $storename = 'modulevars_'.$this->moduleid.'_'.$this->itemtype; //FIXME change id
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
    public function setValue($value)
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
        $isvalid = true;
        $value = null;
        xarVarFetch($name, 'isset', $namevalue,  NULL, XARVAR_DONT_SET);
        if(isset($namevalue)) {
            $value = $namevalue;
        } else {
            xarVarFetch($this->name, 'isset', $fieldvalue,  NULL, XARVAR_DONT_SET);
            if(isset($fieldvalue)) {
                $value = $fieldvalue;
            } else {
                xarVarFetch('dd_'.$this->id, 'isset', $ddvalue,  NULL, XARVAR_DONT_SET);
                if(isset($ddvalue)) {
                    $value = $ddvalue;
                } else {
                    $isvalid = false;
                }
            }
        }
        return array($isvalid,$value);
    }

    /**
     * Check the input value of this property
     *
     * @param string $name name of the input field (default is 'dd_NN' with NN the property id)
     * @param mixed  $value value of the input field (default is retrieved via xarVarFetch())
     */
    public function checkInput($name = '', $value = null)
    {
        if(!isset($value)) {
            list($isvalid,$value) = $this->fetchValue($name);
            if (!$isvalid) {
                $this->invalid = xarML('no value found');
                return false;
            }

            // store the fieldname for validations who need them (e.g. file uploads)
            $name = empty($name) ? 'dd_'.$this->id : $name;
            $this->fieldname = $name;
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
        if(!isset($value)) $value = $this->value;

        $this->value = null;
        $this->invalid = xarML('unknown property');
        return false;
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
     * Get the value of this property's display status
     */
    function getDisplayStatus()
    {
        return ($this->status & DataPropertyMaster::DD_DISPLAYMASK);
    }

    /**
     * Get the value of this property's input status
     */
    function getInputStatus()
    {
        return $this->status - $this->getDisplayStatus();
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
        if(!empty($data['preset']))
            return $this->_showPreset($data);

        if($this->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN || !empty($data['hidden']))
            return $this->showHidden($data);

        if($this->getInputStatus() == DataPropertyMaster::DD_INPUTSTATE_NOINPUT) {
            return $this->showOutput($data) . $this->showHidden($data);
        }

        // Our common items we need
        if(!isset($data['name']))        $data['name'] = 'dd_'.$this->id;
        if(isset($data['fieldprefix']))  $data['name'] = $data['fieldprefix'] . '_' . $data['name'];
        if(!isset($data['id']))          $data['id']   = $data['name'];
        // mod for the tpl and what tpl the prop wants.

        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;

        if(!isset($data['tabindex'])) $data['tabindex'] = 0;
        if(!isset($data['value']))    $data['value']    = '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid: #(1)', $this->invalid) :'';
        // debug($data);
        // Render it
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
        if($this->getDisplayStatus() == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            return $this->showHidden($data);

        $data['id']   = $this->id;
        $data['name'] = $this->name;

        if(!isset($data['value'])) $data['value'] = $this->value;
        // TODO: does this hurt when it is an array?
        if(!isset($data['tplmodule']))   $data['tplmodule']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;

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
        $data['value']    = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->value);
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
    private final function _showPreset(Array $args = array())
    {
        // Check for empty here instead of isset, e.g. for <xar:data-input ... value="" />
        if(empty($args['value']))
        {
            if(empty($args['name']))
                $isvalid = $this->checkInput();
            else
                $isvalid = $this->checkInput($args['name']);

            if($isvalid)
                // remove the original input value from the arguments
                unset($args['value']);
            else
                // clear the invalid message for preset
                $this->invalid = '';
        }

        if(!empty($args['hidden']))
            return $this->showHidden($args);
        else
            return $this->showInput($args);
    }

    /**
     * Parse the validation rule
     *
     * @param string $validation
     */
    public function parseValidation($validation = '')
    {
        // if(... $validation ...) {
        //     $this->whatever = ...;
        // }
    }

    /**
     * The following methods provide an interface to show and update validation rules
     * when editing dynamic properties. They should be customized for each property
     * type, based on its specific format and interpretation of the validation rules.
     *
     * This allows property type developers to support more complex validation rules,
     * while keeping them easy to modify for the site admins afterwards.
     *
     * If no validation methods are specified for a particular property type, the
     * corresponding methods from its parent class will be used.
     *
     * Note: the methods can be called by DD's showpropval() function, or if you set the
     *       type of the 'validation' property (21) to ValidationProperty also
     *       via DD's modify() and update() functions if you edit some dynamic property.
     */

    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showValidation(Array $args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
        $data['size']       = !empty($size) ? $size : 50;
        $data['required']   = isset($required) && $required ? true : false;
        if(!isset($data['module']))   $data['module']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;

        if(isset($validation))
        {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }
        // some known validation rule format
        // if(... $this->whatever ...) {
        //     $data['whatever'] = ...
        //
        // if we didn't match the above format
        // } else {
        $data['other'] = xarVarPrepForDisplay($this->validation);
        // }

        return xarTplProperty($data['module'], $data['template'], 'validation', $data);
    }

    /**
     * Update the current validation rule in a specific way for this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @return bool true if the validation rule could be processed, false otherwise
     */
    public function updateValidation(Array $args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;

        // do something with the validation and save it in $this->validation
        if(isset($validation))
        {
            if(is_array($validation))
            {
                // handle arrays as you like in your property type
                // $this->validation = serialize($validation);
                $this->validation = '';
                $this->invalid = 'array';
                return false;
            }
            else
                $this->validation = $validation;
        }

        // tell the calling function that everything is OK
        return true;
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
