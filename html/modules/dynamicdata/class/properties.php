<?php
/**
 * File: $Id$
 *
 * Dynamic Property Classes
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
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
    function getProperties($args)
    {
        // we can't use our own classes here, because we'd have an endless loop :-)

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $dynamicprop = $xartable['dynamic_properties'];

        $bindvars = array();
        $bindvars = array();
        $query = "SELECT xar_prop_name,
                         xar_prop_label,
                         xar_prop_type,
                         xar_prop_id,
                         xar_prop_default,
                         xar_prop_source,
                         xar_prop_status,
                         xar_prop_order,
                         xar_prop_validation
                  FROM $dynamicprop ";
        if (isset($args['objectid'])) {
            $query .= " WHERE xar_prop_objectid = ?";
            $bindvars[] = $args['objectid'];
        } else {
            $query .= " WHERE xar_prop_moduleid = ?
                          AND xar_prop_itemtype = ?";
            $bindvars[] = $args['moduleid']; $bindvars[] = $args['itemtype'];
        }
        if (empty($args['allprops'])) {
            $query .= " AND xar_prop_status > 0 ";
        }
        $query .= " ORDER BY xar_prop_order ASC, xar_prop_id ASC";

        $result =& $dbconn->Execute($query,$bindvars);

        if (!$result) return;

        $properties = array();
        while (!$result->EOF) {
            list($name, $label, $type, $id, $default, $source, $fieldstatus, $order, $validation) = $result->fields;
            if(xarSecurityCheck('ReadDynamicDataField',0,'Field',"$name:$type:$id")) {
                $property = array('name' => $name,
                                  'label' => $label,
                                  'type' => $type,
                                  'id' => $id,
                                  'default' => $default,
                                  'source' => $source,
                                  'status' => $fieldstatus,
                                  'order' => $order,
                                  'validation' => $validation);
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
    function addProperty($args, &$objectref)
    {
        if (!isset($objectref) || empty($args['name']) || empty($args['type'])) {
            return;
        }

        // "beautify" label based on name if not specified
        if (!isset($args['label']) && !empty($args['name'])) {
            $args['label'] = strtr($args['name'], '_', ' ');
            $args['label'] = ucwords($args['label']);
        }

        // get a new property
        $property =& Dynamic_Property_Master::getProperty($args);

        // for dynamic object lists, put a reference to the $items array in the property
        if (method_exists($objectref, 'getItems')) {
            $property->items =& $objectref->items;
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
    function &getProperty($args)
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
            require_once 'includes/properties/'.$propertyClass.'.php';
            
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
        return $objectid;
    }

    /**
     * Class method listing all defined property types
     */
    function getPropertyTypes()
    {
        if (xarVarIsCached('DynamicData','PropertyTypes')) {
            return xarVarGetCached('DynamicData','PropertyTypes');
        }

        // Attempt to retreive properties from DB
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $dynamicproptypes = $xartable['dynamic_properties_def'];

        $query = "SELECT 
                    xar_prop_id
                    , xar_prop_name
                    , xar_prop_label
                    , xar_prop_parent
                    , xar_prop_filepath
                    , xar_prop_class
                    , xar_prop_format 
                    , xar_prop_validation
                    , xar_prop_source
                    , xar_prop_reqfiles
                    , xar_prop_reqmodules
                    , xar_prop_args
                    , xar_prop_aliases

                  FROM $dynamicproptypes
                  ORDER BY xar_prop_id";

        $result =& $dbconn->Execute($query);

        if (!$result)
        {
            //TODO: Something interesting?  Probably an exception.
            return;
        }

        // If no properties are found, import them in.
        if( $result->EOF)
        {
            $property_types = xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush'=>false));
        } else {
            $property_types = array();
            while (!$result->EOF) 
            {
                list($id,$name,$label,$parent,$filepath,$class,$format,$validation,$source,$reqfiles,$reqmodules,$args,$aliases) = $result->fields;
    
                $property['id']             = $id;
                $property['name']           = $name;
                $property['label']          = $label;
                $property['format']         = $format;
                $property['validation']     = $validation;
                $property['source']         = $source;
                $property['dependancies']   = $reqfiles;
                $property['requiresmodule'] = $reqmodules;
                $property['args']           = $args;
                $property['propertyClass']  = $class;
                $property['aliases']        = $aliases;

                $property_types[$id] = $property;

                $result->MoveNext();
            }
        }
        $result->Close();

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
 */
class Dynamic_Property
{
    var $id = null;
    var $name = null;
    var $label;
    var $type = 1;
    var $default = '';
    var $source = 'dynamic_data';
    var $status = 1;
    var $order;
    var $validation;

    var $datastore = ''; // name of the data store where this property comes from

    var $value = null;   // value of this property for a particular Dynamic_Object
    var $invalid = '';   // result of the checkInput/validateValue methods

    var $items;          // reference to $items in Dynamic_Object_List, where the different item values are kept

    /**
     * Default constructor setting the variables
     */
    function Dynamic_Property($args)
    {
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
     * @param $value value of the input field (default is retrieved via xarVarCleanFromInput())
     */
    function checkInput($name = '', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            $value = xarVarCleanFromInput($name);
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
        return $this->items[$itemid][$this->name];
    }

    /**
     * Set the value of this property for a particular item (= for object lists)
     */
    function setItemValue($itemid, $value)
    {
        $this->items[$itemid][$this->name] = $value;
    }

    /**
     * Show an input field for setting/modifying the value of this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showInput($args = array())
    {
        return xarML('This property is unknown...');
    }

    /**
     * Show some default output for this property
     *
     * @param $args['value'] value of the property (default is the current value)
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showOutput($args = array())
    {
        extract($args);
        if (isset($value)) {
            return xarVarPrepForDisplay($value);
        } else {
            return xarVarPrepForDisplay($this->value);
        }
    }

    /**
     * Show the label for this property
     *
     * @param $label label of the property (default is the current label)
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showLabel($label = null)
    {
        if (isset($label)) {
            return xarVarPrepForDisplay($label);
        } else {
            return xarVarPrepForDisplay($this->label);
        }
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
        return '<input type="hidden"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' />' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
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
                            'id'         => 0,
                            'name'       => 'propertyName',
                            'label'      => 'Property Label',
                            'format'     => '0',
                            'validation' => '',
                            'source'     => '',
                            'dependancies' => '',    // semi-colon seperated list of files that must be present for this property to be available (optional)
                            'requiresmodule' => '', // this module must be available before this property is enabled (optional)
                            'aliases' => '',        // If the same property class is reused directly with just different base info, supply the alternate base properties here (optional)
                            'args' => serialize( array() ),
                            // ...
                           );
        return $baseInfo;
     }
}
?>
