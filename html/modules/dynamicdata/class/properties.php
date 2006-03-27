<?php
/**
 * Utility Class to manage Dynamic Properties
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Utility Class to manage Dynamic Properties
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Property_Master
{
    /**
     * Get the dynamic properties of an object
     *
     * @param $args['objectid'] the object id of the object, or
     * @param $args['moduleid'] the module id of the object +
     * @param $args['itemtype'] the itemtype of the object
     * @param $args['objectref'] a reference to the object to add those properties to (optional)
     * @param $args['allprops'] skip disabled properties by default
     */
    static function getProperties($args)
    {
        // we can't use our own classes here, because we'd have an endless loop :-)

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $dynamicprop = $xartable['dynamic_properties'];

        $bindvars = array();
        $query = "SELECT xar_prop_name, xar_prop_label, xar_prop_type,
                         xar_prop_id, xar_prop_default, xar_prop_source,
                         xar_prop_status, xar_prop_order, xar_prop_validation,
                         xar_prop_objectid, xar_prop_moduleid, xar_prop_itemtype
                  FROM $dynamicprop ";
        if (isset($args['objectid'])) {
            $query .= " WHERE xar_prop_objectid = ?";
            $bindvars[] = (int) $args['objectid'];
        } else {
            $query .= " WHERE xar_prop_moduleid = ?
                          AND xar_prop_itemtype = ?";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }
        if (empty($args['allprops'])) {
            $query .= " AND xar_prop_status > 0 ";
        }
        $query .= " ORDER BY xar_prop_order ASC, xar_prop_id ASC";

        $result =& $dbconn->Execute($query,$bindvars);

        $properties = array();
        while (!$result->EOF) {
            list($name, $label, $type, $id, $default, $source, $fieldstatus, $order, $validation,
                 $_objectid, $_moduleid, $_itemtype) = $result->fields;
            if(xarSecurityCheck('ReadDynamicDataField',0,'Field',"$name:$type:$id")) {
                $property = array('name' => $name,
                                  'label' => $label,
                                  'type' => $type,
                                  'id' => $id,
                                  'default' => $default,
                                  'source' => $source,
                                  'status' => $fieldstatus,
                                  'order' => $order,
                                  'validation' => $validation,
                                  // some internal variables
                                  '_objectid' => $_objectid,
                                  '_moduleid' => $_moduleid,
                                  '_itemtype' => $_itemtype);
                if (isset($args['objectref'])) {
                    Dynamic_Property_Master::addProperty($property,$args['objectref']);
                } else {
                    $properties[$name] = $property;
                }
            }
            $result->MoveNext();
        }
        $result->Close();

        return $properties;
    }

    /**
     * Add a dynamic property to an object
     *
     * @param $args['name'] the name for the dynamic property
     * @param $args['type'] the type of dynamic property
     * @param $args['label'] the label for the dynamic property
     * ...
     * @param $objectref a reference to the object to add this property to
     */
    static function addProperty($args, &$objectref)
    {
        if (!isset($objectref) || empty($args['name']) || empty($args['type'])) {
            return;
        }

        // "beautify" label based on name if not specified
        // TODO: this is a presentation issue, doesnt belong here.
        if (!isset($args['label']) && !empty($args['name'])) {
            $args['label'] = strtr($args['name'], '_', ' ');
            $args['label'] = ucwords($args['label']);
        }

        // get a new property
        $property =& Dynamic_Property_Master::getProperty($args);

        // for dynamic object lists, put a reference to the $items array in the property
        if (method_exists($objectref, 'getItems')) {
            $property->_items =& $objectref->items;

        // for dynamic objects, put a reference to the $itemid value in the property
        } elseif (method_exists($objectref, 'getItem')) {
            $property->_itemid =& $objectref->itemid;
        }

        // add it to the list of properties
        $objectref->properties[$property->name] =& $property;

        if (isset($property->upload)) {
            $objectref->upload = true;
        }
    }

    /**
     * Class method to get a new dynamic property of the right type
     */
    static function &getProperty($args)
    {
        if (!is_numeric($args['type']))
        {
            $proptypes = Dynamic_Property_Master::getPropertyTypes();
            if (!isset($proptypes)) {
                $proptypes = array();
            }
            foreach ($proptypes as $typeid => $proptype) {
                if ($proptype['name'] == $args['type']) {
                    $args['type'] = $typeid;
                    break;
                }
            }
        } else {
            $proptypes = Dynamic_Property_Master::getPropertyTypes();
        }
        
        if( isset($proptypes[$args['type']]) && is_array($proptypes[$args['type']]) )
        {
            $propertyInfo  = $proptypes[$args['type']];
            $propertyClass = $propertyInfo['propertyClass'];
            // Filepath is complete rel path to the php file, and decoupled from the class name
            // We should load the MLS translations for the right context here, in case the property
            // PHP file contains xarML() statements
            // See bug 5097
            if(preg_match('/modules\/(.*)\/xarproperties/',$propertyInfo['filepath'],$matches) == 1) {
                // The preg determines the module name (in a sloppy way, FIX this)
                xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE,$matches[1],'modules:properties',$propertyClass);
            } else xarLogMessage("WARNING: Property translations for $propertyClass NOT loaded");
            
            if(!file_exists($propertyInfo['filepath'])) throw new FileNotFoundException($propertyInfo['filepath']);
            require_once $propertyInfo['filepath'];


            if( isset($propertyInfo['args']) && ($propertyInfo['args'] != '') )
            {
                $baseArgs = unserialize($propertyInfo['args']);
                $args = array_merge($baseArgs, $args);
            }

            $property = new $propertyClass($args);
        } else {
            $property = new Dynamic_Property($args);
        }

        return $property;
    }

    function createProperty($args)
    {
        $object = new Dynamic_Object(array('objectid' => 2)); // the Dynamic Properties = 2
        $objectid = $object->createItem($args);
        unset($object);
        return $objectid;
    }

    function updateProperty($args)
    {
        // TODO: what if the property type changes to something incompatible ?
    }

    function deleteProperty($args)
    {
        if (empty($args['itemid'])) return;

        // TODO: delete all the (dynamic ?) data for this property as well
        $object = new Dynamic_Object(array('objectid' => 2, // the Dynamic Properties = 2
                                           'itemid'   => $args['itemid']));
        if (empty($object)) return;

        $objectid = $object->getItem();
        if (empty($objectid)) return;

        $objectid = $object->deleteItem();
        unset($object);
        return $objectid;
    }

    /**
     * Class method listing all defined property types
     */
    static function getPropertyTypes()
    {
        //if (xarVarIsCached('DynamicData','PropertyTypes')) {
        //  return xarVarGetCached('DynamicData','PropertyTypes');
        //}

        // Attempt to retreive properties from DB
        $property_types =& PropertyRegistration::Retrieve();

        /*
         // Security Check
         if (xarSecurityCheck('ViewDynamicData',0)) {
             $proptypes[] = array(...);
         }
         }
        */
        xarVarSetCached('DynamicData','PropertyTypes',$property_types);
        return $property_types;
    }

}

/**
 * Base Class for Dynamic Properties
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 * @todo is this abstract?
 * @todo the visibility of most of the attributes can probably be protected
 */
class Dynamic_Property
{
    // Attributes for registration
    public $id = 0;
    public $name = 'propertyName';
    public $label = 'Property Label';
    public $type = 1;
    public $default = '';
    public $source = 'dynamic_data';
    public $status = 1;
    public $order = 0;
    public $format = '0';
    public $requiresmodule = ''; // this module must be available before this property is enabled (optional)
    public $aliases = '';        // If the same property class is reused directly with just different base info, supply the alternate base properties here (optional)

    // Attributes for runtime
    public $template = '';
    public $tplmodule = 'dynamicdata';
    public $validation = '';
    public $dependancies = '';    // semi-colon seperated list of files that must be present for this property to be available (optional)
    public $args;

    public $datastore = '';   // name of the data store where this property comes from

    public $value = null;     // value of this property for a particular Dynamic_Object
    public $invalid = '';     // result of the checkInput/validateValue methods

    public $_objectid = null; // objectid this property belongs to
    public $_moduleid = null; // moduleid this property belongs to
    public $_itemtype = null; // itemtype this property belongs to

    public $_itemid;          // reference to $itemid in Dynamic_Object, where the current itemid is kept
    public $_items;           // reference to $items in Dynamic_Object_List, where the different item values are kept

    /**
     * Default constructor setting the variables
     */
    function __construct($args)
    {
	    $this->args = serialize(array());

        if (!empty($args) && is_array($args) && count($args) > 0) {
            foreach ($args as $key => $val) {
                $this->$key = $val;
            }
        }
        if (!isset($args['value'])) {
            // if the default field looks like xar<something>(...), we'll assume that this is
            // a function call that returns some dynamic default value
            if (!empty($this->default) && preg_match('/^xar\w+\(.*\)$/',$this->default)) {
                eval('$value = ' . $this->default .';');
                if (isset($value)) {
                    $this->default = $value;
                } else {
                    $this->default = null;
                }
            }
            $this->value = $this->default;
        }
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * @returns mixed
     * @return the value for the property
     */
    function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param $value the new value for the property
     */
    function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Check the input value of this property
     *
     * @param $name name of the input field (default is 'dd_NN' with NN the property id)
     * @param $value value of the input field (default is retrieved via xarVarFetch())
     */
    function checkInput($name = '', $value = null)
    {
        if (!isset($value)) {
            $isvalid = true;
            xarVarFetch('dd_'.$this->id, 'isset', $ddvalue,  NULL, XARVAR_NOT_REQUIRED);
            if (isset($ddvalue)) {
                $value = $ddvalue;
            } else {
                xarVarFetch($this->name, 'isset', $fieldvalue,  NULL, XARVAR_NOT_REQUIRED);
                if (isset($fieldvalue)) {
                    $value = $fieldvalue;
                } else {
                    xarVarFetch($name, 'isset', $namevalue,  NULL, XARVAR_NOT_REQUIRED);
                    if (isset($namevalue)) {
                        $value = $namevalue;
                    } else {
                        $isvalid = false;
                    }
                }
            }
            if (!$isvalid) {
            /*
                $msg = 'Field #(1) (dd_#(2)) is missing.';
                if (!empty($name)) {
                    $vars = array($name,$this->id);
                } else {
                    $vars = array($this->name,$this->id);
                }
                throw new BadParameterException($vars,$msg);
            */
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
     * @param $value value of the property (default is the current value)
     */
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        $this->value = null;
        $this->invalid = xarML('unknown property');
        return false;
    }

    /**
     * Get the value of this property for a particular item (= for object lists)
     *
     * @param $itemid the item id we want the value for
     */
    function getItemValue($itemid)
    {
        return $this->_items[$itemid][$this->name];
    }

    /**
     * Set the value of this property for a particular item (= for object lists)
     */
    function setItemValue($itemid, $value)
    {
        $this->_items[$itemid][$this->name] = $value;
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
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showInput($data = array())
    {
        // Our common items we need
        if(!isset($data['name']))     $data['name']     = 'dd_'.$this->id;
        if(!isset($data['id']))       $data['id']       = $data['name'];
        // mod for the tpl and what tpl the prop wants.
        if(!isset($data['module']))   $data['module']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;

        if(!isset($data['tabindex'])) $data['tabindex'] = 0;
        if(!isset($data['value']))    $data['value']    = '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        // debug($data);
        // Render it
        return xarTplProperty($data['module'], $data['template'], 'showinput', $data);
    }

    /**
     * Show some default output for this property
     *
     * @param $args['value'] value of the property (default is the current value)
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showOutput($data = array())
    {
        $data['id']   = $this->id;
        $data['name'] = $this->name;

        if (!isset($data['value'])) $data['value'] = $this->value;
        // TODO: does this hurt when it is an array?
        if(is_string($data['value']))
            $data['value'] = xarVarPrepForDisplay($data['value']);
        if(!isset($data['module']))   $data['module']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        // Render it
        return xarTplProperty($data['module'], $data['template'], 'showoutput', $data);
    }

    /**
     * Show the label for this property
     *
     * @param $args['label'] label of the property (default is the current label)
     * @param $args['for'] label id to use for this property (id, name or nothing)
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showLabel($args = array())
    {
        if (empty($args)) {

        // old syntax was showLabel($label = null)
        } elseif (is_string($args)) {
            $label = $args;

        } elseif (is_array($args)) {
            extract($args);
        }

        $data = array();
        $data['id']    = $this->id;
        $data['name']  = $this->name;
        $data['label'] = isset($label) ? xarVarPrepForDisplay($label) : xarVarPrepForDisplay($this->label);
        $data['for']   = isset($for) ? $for : null;

        if (!isset($template)) {
            $template = null;
        }
        return xarTplProperty('dynamicdata', $template, 'label', $data);
    }

    /**
     * Show a hidden field for this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showHidden($args = array())
    {
        extract($args);

        $data = array();
        $data['name']     = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']       = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (!isset($template)) {
            $template = null;
        }
        return xarTplProperty('dynamicdata', $template, 'showhidden', $data);
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
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function _showPreset($args = array())
    {
        // Check for empty here instead of isset, e.g. for <xar:data-input ... value="" />
        if (empty($args['value'])) {
            if (empty($args['name'])) {
                $isvalid = $this->checkInput();
            } else {
                $isvalid = $this->checkInput($args['name']);
            }
            if ($isvalid) {
                // remove the original input value from the arguments
                unset($args['value']);
            } else {
                // clear the invalid message for preset
                $this->invalid = '';
            }
        }

        if (!empty($args['hidden'])) {
            return $this->showHidden($args);
        } else {
            return $this->showInput($args);
        }
    }

    /**
     * Parse the validation rule
     */
    function parseValidation($validation = '')
    {
        // if (... $validation ...) {
        //     $this->whatever = ...;
        // }
    }

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
    function getBasePropertyInfo()
    {
        $baseInfo = array(
                          'id'         => $this->id,
                          'name'       => $this->name,
                          'label'      => $this->label,
                          'format'     => $this->format,
                          'template'   => $this->template,
                          'tplmodule'  => $this->tplmodule,
                          'validation' => $this->validation,
                          'source'     => $this->source,
                          'dependancies' => $this->dependancies,
                          'requiresmodule' => $this->requiresmodule,
                          'aliases' => $this->aliases,
                          'args' => $this->args
                          // ...
                         );
        return $baseInfo;
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
     *       type of the 'validation' property (21) to Dynamic_Validation_Property also
     *       via DD's modify() and update() functions if you edit some dynamic property.
     */

    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
        $data['size']       = !empty($size) ? $size : 50;

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }
        // some known validation rule format
        // if (... $this->whatever ...) {
        //     $data['whatever'] = ...
        //
        // if we didn't match the above format
        // } else {
        $data['other'] = xarVarPrepForDisplay($this->validation);
        // }

        // allow template override by child classes
        if (!isset($template)) {
            $template = null;
        }
        return xarTplProperty('dynamicdata', $template, 'validation', $data);
    }

    /**
     * Update the current validation rule in a specific way for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @returns bool
     * @return bool true if the validation rule could be processed, false otherwise
     */
    function updateValidation($args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }

        // do something with the validation and save it in $this->validation
        if (isset($validation)) {
            if (is_array($validation)) {
                // handle arrays as you like in your property type
                // $this->validation = serialize($validation);
                $this->validation = '';
                $this->invalid = 'array';
                return false;

            } else {
                $this->validation = $validation;
            }
        }

        // tell the calling function that everything is OK
        return true;
    }

    /**
     * Return the module this property belongs to
     *
     * @returns string module name
     */
    function getModule()
    {
        $info = $this->getBasePropertyInfo();
        $modulename = empty($this->tplmodule) ? $info['tplmodule'] : $this->tplmodule;
        return $modulename;
    }
    /**
     * Return the name this property uses in its templates
     *
     * @returns string template name
     */
    function getTemplate()
    {
        // If not specified, default to the registered name of the prop
        $info = $this->getRegistrationInfo();
        $template = empty($this->template) ? $info->name : $this->template;
        return $template;
    }
}
/**
 * Class to model registration information for a property
 *
 * This corresponds directly to the db info we register for a property.
 *
 */
class PropertyRegistration
{
    public $id         = 0;                      // id of the property, hardcoded to make things easier
    public $name       = 'propertyType';         // what type of property are we dealing with
    public $desc       = 'Property Description'; // description of this type
    public $type       = 1;
    public $parent     = '';                     // this type is derived from?
    public $filepath   = '';                     // where is our class for it?
    public $class      = '';                     // what is the class?
    public $validation = '';                     // what is its default validation?
    public $source     = 'dynamic_data';         // what source is default for this type?
    public $reqfiles   = array();                // do we require some files to be present?
    public $reqmodules = array();                // do we require some modules to be present?
    public $args       = '';                     // special args needed?
    public $aliases    = array();                // aliases for this property
    public $format     = 0;                      // what format type do we have here?
                                                 // 0 = ? what?
                                                 // 1 = 
    
    function __construct($args=array()) 
    {
        assert('is_array($args)');
        if(!empty($args)) {
            foreach($args as $key=>$value) {
                $this->$key = $value;
            }
        }
    }
    
    static function clearCache() 
    {
        $dbconn = &xarDBGetConn();
        $tables = xarDBGetTables();
        $sql = "DELETE FROM $tables[dynamic_properties_def]";
        $res = $dbconn->ExecuteUpdate($sql);
        return $res;
    }

    function Register() 
    {
        static $stmt = null;

        // Sanity checks (silent)
        foreach($this->reqfiles as $required) {
            if(!file_exists($required)) return false;
        }
        foreach($this->reqmodules as $required) {
            if(!xarModIsAvailable($required)) return false;
        }
            
        $dbconn = &xarDBGetConn();
        $tables = xarDBGetTables();
        $propdefTable = $tables['dynamic_properties_def'];
        
        // Make sure the db is the same as in the old days
        $reqmods = join(';',$this->reqmodules);
        if($this->format == 0) $this->format = $this->id;

        $sql = "INSERT INTO $propdefTable
                (xar_prop_id, xar_prop_name, xar_prop_label,
                 xar_prop_parent, xar_prop_filepath, xar_prop_class,
                 xar_prop_format, xar_prop_validation, xar_prop_source,
                 xar_prop_reqfiles, xar_prop_reqmodules, xar_prop_args, xar_prop_aliases)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        if(!isset($stmt)) {
            $stmt = $dbconn->prepareStatement($sql);
        }
        $bindvars = array(
                          (int) $this->id, $this->name, $this->desc,
                          $this->parent, $this->filepath, $this->class,
                          $this->format, $this->validation, $this->source,
                          $this->reqfiles, $reqmods, $this->args, $this->aliases);
        $res = $stmt->executeUpdate($bindvars);

        if(!empty($this->aliases)) {
            foreach($this->aliases as $aliasInfo) {
                $aliasInfo->filepath = $this->filepath; // Make sure
                $aliasInfo->class = $this->class;
                $aliasInfo->format = $this->format;
                $aliasInfo->reqmodules = $this->reqmodules;
                // Recursive!!
                $res = $aliasInfo->Register();
            }
        }
        return $res;                          
    }

    static function &Retrieve()
    {
        $dbconn =& xarDBGetConn();
        $tables = xarDBGetTables();
        // Sort by required module(s) and then by name
        $query = "SELECT  xar_prop_id, xar_prop_name, xar_prop_label,
                          xar_prop_parent, xar_prop_filepath, xar_prop_class,
                          xar_prop_format, xar_prop_validation, xar_prop_source,
                          xar_prop_reqfiles,xar_prop_reqmodules, xar_prop_args,
                          xar_prop_aliases
                  FROM    $tables[dynamic_properties_def]
                  ORDER BY xar_prop_reqmodules, xar_prop_name";
        $result =& $dbconn->executeQuery($query);
        $proptypes = array();
        if($result->RecordCount() == 0 ) {
            $proptypes = xarModAPIFunc('dynamicdata','admin','importpropertytypes',array('flush'=>false));
        } else {
            while($result->next()) {
                list($id,$name,$label,$parent,$filepath,$class,$format,$validation,$source,$reqfiles,$reqmodules,$args,$aliases) = $result->fields;

                $property['id']             = $id;
                $property['name']           = $name;
                $property['label']          = $label;
                $property['format']         = $format;
                $property['filepath']       = $filepath;
                $property['validation']     = $validation;
                $property['source']         = $source;
                $property['dependancies']   = $reqfiles;
                $property['requiresmodule'] = $reqmodules;
                $property['args']           = $args;
                $property['propertyClass']  = $class;
                // TODO: this return a serialized array of objects, does that hurt?
                $property['aliases']        = $aliases;

                $proptypes[$id] = $property;
            }
        }
        $result->close();
        return $proptypes;
    }   
}
?>
