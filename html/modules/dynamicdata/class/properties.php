<?php
/**
 * File: $Id$
 *
 * Dynamic Property Classes
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
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
     * @param $objectref a reference to the object to add those properties to (optional)
     */
    function &getProperties($args, &$objectref)
    {
        // we can't use our own classes here, because we'd have an endless loop :-)
/*
        if ($args['objectid']) {
            $where = 'objectid eq ' . $args['objectid'];
        } else {
            $where = 'moduleid eq ' . $args['moduleid'] . ' and itemtype eq ' .  $args['itemtype'];
        }
        $propertylist = new Dynamic_Object_List(array('objectid' => 2, 'where' => $where));
        $propertyitems = $propertylist->getItems();
        foreach ($propertyitems as $id => $property) {
            if (xarSecAuthAction(0, 'DynamicData::Field', "$property[name]:$property[type]:$property[id]", ACCESS_READ)) {
                $this->addProperty($property,$objectref);
            }
        }
*/
/**/
        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicprop = $xartable['dynamic_properties'];

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
            $query .= " WHERE xar_prop_objectid = " . xarVarPrepForStore($args['objectid']);
        } else {
            $query .= " WHERE xar_prop_moduleid = " . xarVarPrepForStore($args['moduleid']) . "
                          AND xar_prop_itemtype = " . xarVarPrepForStore($args['itemtype']);
        }
        $query .= " ORDER BY xar_prop_order ASC, xar_prop_id ASC";

        $result =& $dbconn->Execute($query);

        if (!$result) return;

        while (!$result->EOF) {
            list($name, $label, $type, $id, $default, $source, $fieldstatus, $order, $validation) = $result->fields;
            if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
                $property = array('name' => $name,
                                  'label' => $label,
                                  'type' => $type,
                                  'id' => $id,
                                  'default' => $default,
                                  'source' => $source,
                                  'status' => $fieldstatus,
                                  'order' => $order,
                                  'validation' => $validation);
                if (isset($objectref)) {
                    Dynamic_Property_Master::addProperty($property,$objectref);
                }
            }
            $result->MoveNext();
        }

        $result->Close();
/**/
    }

    /**
     * Add a dynamic property to an object
     *
     * @param $args['name'] the name for the dynamic property
     * @param $args['label'] the label for the dynamic property
     * @param $args['type'] the type of dynamic property
     * ...
     * @param $objectref a reference to the object to add this property to
     */
    function addProperty($args, &$objectref)
    {
        if (isset($objectref)) {
            // get a new property
            $property = Dynamic_Property_Master::getProperty($args);

            // add it to the list of properties
            $objectref->properties[$property->name] = $property;
        }
    }

    /**
     * Class method to get a new dynamic property of the right type
     */
    function getProperty($args)
    {
        if (!is_numeric($args['type'])) {
            $proptypes = Dynamic_Property_Master::getPropertyTypes();
            foreach ($proptypes as $typeid => $proptype) {
                if ($proptype['name'] == $args['type']) {
                    $args['type'] = $typeid;
                    break;
                }
            }
        }
        switch ($args['type'])
        {
            case 1: // (static) Static Text
                $property = new Dynamic_StaticText_Property($args);
                break;
            case 2: // (textbox) Text Box
                $property = new Dynamic_TextBox_Property($args);
                break;
            case 3: // (textarea_small) Small Text Area
                $args['rows'] = 2;
                $property = new Dynamic_TextArea_Property($args);
                break;
            case 4: // (textarea_medium) Medium Text Area
                $args['rows'] = 8;
                $property = new Dynamic_TextArea_Property($args);
                break;
            case 5: // (textarea_large) Large Text Area
                $args['rows'] = 20;
                $property = new Dynamic_TextArea_Property($args);
                break;
            case 6: // (dropdown) Dropdown List
                $property = new Dynamic_Select_Property($args);
                break;
            case 7: // (username) Username
                $property = new Dynamic_Username_Property($args);
                break;
            case 8: // (calendar) Calendar
                $property = new Dynamic_Calendar_Property($args);
                break;
            case 9: // (fileupload) File Upload
                $property = new Dynamic_FileUpload_Property($args);
                break;
            case 10: // (status) Status
                $property = new Dynamic_Status_Property($args);
                break;
            case 11: // (url) URL
                $property = new Dynamic_URL_Property($args);
                break;
            case 12: // (image) Image
                $property = new Dynamic_Image_Property($args);
                break;
            case 13: // (webpage) HTML Page
                $property = new Dynamic_HTMLPage_Property($args);
                break;
            case 14: // (checkbox) Checkbox
                $property = new Dynamic_Checkbox_Property($args);
                break;
            case 15: // (integerbox) Number Box
                $property = new Dynamic_NumberBox_Property($args);
                break;
            case 16: // (integerlist) Number List
                $property = new Dynamic_NumberList_Property($args);
                break;
            case 17: // (floatbox) Number Box (float)
                $property = new Dynamic_FloatBox_Property($args);
                break;
            case 18: // (hidden) Hidden
                $property = new Dynamic_Hidden_Property($args);
                break;
            case 19: // (module) Module
                $property = new Dynamic_Module_Property($args);
                break;
            case 20: // (itemtype) Item Type
                $property = new Dynamic_ItemType_Property($args);
                break;
            case 21: // (itemid) Item ID
                $property = new Dynamic_ItemID_Property($args);
                break;
            case 22: // (fieldtype) Field Type
                $property = new Dynamic_FieldType_Property($args);
                break;
            case 23: // (datasource) Data Source
                $property = new Dynamic_DataSource_Property($args);
                break;
            case 24: // (object) Object
                $property = new Dynamic_Object_Property($args);
                break;
            case 25: // (fieldstatus) Field Status
                $property = new Dynamic_FieldStatus_Property($args);
                break;
            default:
                $property = new Dynamic_Property($args);
                break;
        }
        return $property;
    }

    function createProperty($args)
    {
    }

    function deleteProperty($args)
    {
    }

    function deleteProperty($args)
    {
    }

    /**
     * Class method listing all defined property types
     */
    function getPropertyTypes()
    {
        $proptypes = array();

    // TODO: replace with something else
        $proptypes[1] = array(
                              'id'         => 1,
                              'name'       => 'static',
                              'label'      => 'Static Text',
                              'format'     => '1',
                              'validation' => '',
                              // ...
                             );
        $proptypes[2] = array(
                              'id'         => 2,
                              'name'       => 'textbox',
                              'label'      => 'Text Box',
                              'format'     => '2',
                              'validation' => '',
                              // ...
                             );
        $proptypes[3] = array(
                              'id'         => 3,
                              'name'       => 'textarea_small',
                              'label'      => 'Small Text Area',
                              'format'     => '3',
                              'validation' => '',
                              // ...
                             );
        $proptypes[4] = array(
                              'id'         => 4,
                              'name'       => 'textarea_medium',
                              'label'      => 'Medium Text Area',
                              'format'     => '4',
                              'validation' => '',
                              // ...
                             );
        $proptypes[5] = array(
                              'id'         => 5,
                              'name'       => 'textarea_large',
                              'label'      => 'Large Text Area',
                              'format'     => '5',
                              'validation' => '',
                              // ...
                             );
        $proptypes[6] = array(
                              'id'         => 6,
                              'name'       => 'dropdown',
                              'label'      => 'Dropdown List',
                              'format'     => '6',
                              'validation' => '',
                              // ...
                             );
        $proptypes[7] = array(
                              'id'         => 7,
                              'name'       => 'username',
                              'label'      => 'Username',
                              'format'     => '7',
                              'validation' => '',
                              // ...
                             );
        $proptypes[8] = array(
                              'id'         => 8,
                              'name'       => 'calendar',
                              'label'      => 'Calendar',
                              'format'     => '8',
                              'validation' => '',
                              // ...
                             );
        $proptypes[9] = array(
                              'id'         => 9,
                              'name'       => 'fileupload',
                              'label'      => 'File Upload',
                              'format'     => '9',
                              'validation' => '',
                              // ...
                             );
        $proptypes[10] = array(
                              'id'         => 10,
                              'name'       => 'status',
                              'label'      => 'Status',
                              'format'     => '10',
                              'validation' => '',
                              // ...
                             );
        $proptypes[11] = array(
                              'id'         => 11,
                              'name'       => 'url',
                              'label'      => 'URL',
                              'format'     => '11',
                              'validation' => '',
                              // ...
                             );
        $proptypes[12] = array(
                              'id'         => 12,
                              'name'       => 'image',
                              'label'      => 'Image',
                              'format'     => '12',
                              'validation' => '',
                              // ...
                             );
        $proptypes[13] = array(
                              'id'         => 13,
                              'name'       => 'webpage',
                              'label'      => 'HTML Page',
                              'format'     => '13',
                              'validation' => '',
                              // ...
                             );
        $proptypes[14] = array(
                              'id'         => 14,
                              'name'       => 'checkbox',
                              'label'      => 'Checkbox',
                              'format'     => '14',
                              'validation' => '',
                              // ...
                             );
        $proptypes[15] = array(
                              'id'         => 15,
                              'name'       => 'integerbox',
                              'label'      => 'Number Box',
                              'format'     => '15',
                              'validation' => '',
                              // ...
                             );
        $proptypes[16] = array(
                              'id'         => 16,
                              'name'       => 'integerlist',
                              'label'      => 'Number List',
                              'format'     => '16',
                              'validation' => '',
                              // ...
                             );
        $proptypes[17] = array(
                              'id'         => 17,
                              'name'       => 'floatbox',
                              'label'      => 'Number Box (float)',
                              'format'     => '17',
                              'validation' => '',
                              // ...
                             );
        $proptypes[18] = array(
                              'id'         => 18,
                              'name'       => 'hidden',
                              'label'      => 'Hidden',
                              'format'     => '18',
                              'validation' => '',
                              // ...
                             );
    // handy for relationships, URLs etc.
        $proptypes[19] = array(
                              'id'         => 19,
                              'name'       => 'module',
                              'label'      => 'Module',
                              'format'     => '19',
                              'validation' => '',
                              // ...
                             );
        $proptypes[20] = array(
                              'id'         => 20,
                              'name'       => 'itemtype',
                              'label'      => 'Item Type',
                              'format'     => '20',
                              'validation' => '',
                              // ...
                             );
        $proptypes[21] = array(
                              'id'         => 21,
                              'name'       => 'itemid',
                              'label'      => 'Item ID',
                              'format'     => '21',
                              'validation' => '',
                              // ...
                             );
        $proptypes[22] = array(
                              'id'         => 22,
                              'name'       => 'fieldtype',
                              'label'      => 'Field Type',
                              'format'     => '22',
                              'validation' => '',
                              // ...
                             );
        $proptypes[23] = array(
                              'id'         => 23,
                              'name'       => 'datasource',
                              'label'      => 'Data Source',
                              'format'     => '23',
                              'validation' => '',
                              // ...
                             );
        $proptypes[24] = array(
                              'id'         => 24,
                              'name'       => 'object',
                              'label'      => 'Object',
                              'format'     => '24',
                              'validation' => '',
                              // ...
                             );
        $proptypes[25] = array(
                              'id'         => 25,
                              'name'       => 'fieldstatus',
                              'label'      => 'Field Status',
                              'format'     => '25',
                              'validation' => '',
                              // ...
                             );

        // add some property types supported by utility modules
        if (xarModIsAvailable('categories') && xarModAPILoad('categories','user')) {
            $proptypes[100] = array(
                                    'id'         => 100,
                                    'name'       => 'categories',
                                    'label'      => 'Categories',
                                    'format'     => '100',
                                    'validation' => '',
                                    'source'     => 'hook module',
                                    // ...
                                  );
        }
        if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','user')) {
            $proptypes[101] = array(
                                    'id'         => 101,
                                    'name'       => 'hitcount',
                                    'label'      => 'Hit Count',
                                    'format'     => '101',
                                    'validation' => '',
                                    'source'     => 'hook module',
                                    // ...
                                   );
        }
        if (xarModIsAvailable('ratings') && xarModAPILoad('ratings','user')) {
            $proptypes[102] = array(
                                    'id'         => 102,
                                    'name'       => 'ratings',
                                    'label'      => 'Rating',
                                    'format'     => '102',
                                    'validation' => '',
                                    'source'     => 'hook module',
                                    // ...
                                   );
        }
        if (xarModIsAvailable('comments') && xarModAPILoad('comments','user')) {
            $proptypes[103] = array(
                                    'id'         => 103,
                                    'name'       => 'comments',
                                    'label'      => 'Comments',
                                    'format'     => '103',
                                    'validation' => '',
                                    'source'     => 'hook module',
                                    // ...
                                   );
        }
    // trick : retrieve the number of comments via a user function here
        if (xarModIsAvailable('comments') && xarModAPILoad('comments','user')) {
            $proptypes[104] = array(
                                    'id'         => 104,
                                    'name'       => 'numcomments',
                                    'label'      => '# of Comments',
                                    'format'     => '104',
                                    'validation' => 'comments_userapi_get_count',
                                    'source'     => 'user function',
                                    // ...
                                   );
        }
    // TODO: replace fileupload above with this one someday ?
    /*
        if (xarModIsAvailable('uploads') && xarModAPILoad('uploads','user')) {
            $proptypes[105] = array(
                                    'id'         => 105,
                                    'name'       => 'uploads',
                                    'label'      => 'Upload',
                                    'format'     => '105',
                                    'validation' => '',
                                    'source'     => 'hook module',
                                    // ...
                                   );
        }
    */

    // TODO: yes :)
    /*
        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicproptypes = $xartable['dynamic_property_types'];

        $query = "SELECT ...
                  FROM $dynamicproptypes";

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        while (!$result->EOF) {
            list(...) = $result->fields;
            if (xarSecAuthAction(0, '...', "...", ACCESS_OVERVIEW)) {
                $proptypes[] = array(...);
            }
            $result->MoveNext();
        }

        $result->Close();
    */

        return $proptypes;
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
    var $id;
    var $name;
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
     * @param $value value of the property (default is the current value)
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showOutput($value = null)
    {
        if (isset($value)) {
            return xarVarPrepForDisplay($value);
        } else {
            return xarVarPrepForDisplay($this->value);
        }
    }

}


/**
 * Dynamic Static Text Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_StaticText_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('static text');
            $this->value = null;
            return false;
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        return (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    // default showOutput() from Dynamic_Property
}

/**
 * Dynamic Text Box Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_TextBox_Property extends Dynamic_Property
{
    var $size = 50;
    var $maxlength = 254;

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && strlen($value) > $this->maxlength) {
            $this->invalid = xarML('text too long');
            $this->value = null;
            return false;
        } else {
    // TODO: allowable HTML ?
            $this->value = $value;
            return true;
        }
    }

//    function showInput($name = '', $value = null, $size = 0, $maxlength = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        return '<input type="text"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (isset($value)) {
            return xarVarPrepHTMLDisplay($value);
        } else {
            return xarVarPrepHTMLDisplay($this->value);
        }
    }

}

/**
 * Dynamic Text Area Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_TextArea_Property extends Dynamic_Property
{
    var $rows = 8;
    var $cols = 50;
    var $wrap = 'soft';

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
    // TODO: allowable HTML ?
        $this->value = $value;
        return true;
    }

//    function showInput($name = '', $value = null, $rows = 8, $cols = 50, $wrap = 'soft', $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        return '<textarea' .
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' rows="'. (!empty($rows) ? $rows : $this->rows) . '"' .
               ' cols="'. (!empty($cols) ? $cols : $this->cols) . '"' .
               ' wrap="'. (!empty($wrap) ? $wrap : $this->wrap) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               '>' . (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '</textarea>' .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (isset($value)) {
            return xarVarPrepHTMLDisplay($value);
        } else {
            return xarVarPrepHTMLDisplay($this->value);
        }
    }

}


/**
 * Dynamic Select Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Select_Property extends Dynamic_Property
{
    var $options;

    function Dynamic_Select_Property($args)
    {
        $this->Dynamic_Property($args);
        if (!isset($this->options)) {
            $this->options = array();
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        foreach ($this->options as $option) {
            if ($option['id'] == $value) {
                $this->value = $value;
                return true;
            }
        }
        $this->invalid = xarML('selection');
        $this->value = null;
        return false;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        $out = '<select' .
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               '>';
        foreach ($options as $option) {
            if ($option['id'] == $value) {
                $out .= '<option value="'.$option['id'].'" selected>'.$option['name'].'</option>';
            } else {
                $out .= '<option value="'.$option['id'].'">'.$option['name'].'</option>';
            }
        }
        $out .= '</select>' .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        return $out;
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        $out = '';
    // TODO: support multiple selection
        $join = '';
        foreach ($this->options as $option) {
            if ($option['id'] == $value) {
                $out .= $join . xarVarPrepForDisplay($option['name']);
                $join = ' | ';
            }
        }
        return $out;
    }

}


/**
 * Dynamic Username Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Username_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = xarUserGetVar('uid');
        }
        // check that the user exists
        if (is_numeric($value)) {
            $user = xarUserGetVar('uname', $value);
        }
        if (!is_numeric($value) || empty($user)) {
            $this->invalid = xarML('user');
            $this->value = null;
            return false;
        } else {
            $this->value = $value;
            return true;
        }
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        return '<input type="text"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = xarUserGetVar('uid');
        }
        $user = xarUserGetVar('name', $value);
        if (empty($user)) {
            $user = xarUserGetVar('uname', $value);
        }
        if ($value > 1) {
            return '<a href="'.xarModURL('users','user','display',
                                         array('uid' => $value))
                    . '">'.xarVarPrepForDisplay($user).'</a>';
        } else {
            return xarVarPrepForDisplay($user);
        }
    }

}

/**
 * Dynamic Calendar Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Calendar_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is now
        if (empty($value)) {
            $this->value = time();
        } elseif (is_numeric($value)) {
            $this->value = $value;
        } elseif (is_array($value) && !empty($value['year'])) {
            if (!isset($value['sec'])) {
                $value['sec'] = 0;
            }
            $this->value = mktime($value['hour'],$value['min'],$value['sec'],
                                  $value['mon'],$value['mday'],$value['year']);
        } elseif (is_string($value)) {
            // assume dates are stored in UTC format
        // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            $this->value = strtotime($value);
        } else {
            $this->invalid = xarML('date');
            $this->value = null;
            return false;
        }
        // TODO: improve this
        if ($this->validation == 'datetime') {
            $this->value = gmdate('Y-m-d H:i:s', $this->value);
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = time();
        } elseif (is_string($value)) {
            // assume dates are stored in UTC format
        // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            $value = strtotime($value);
        }
        $output = '';
    // TODO: adapt to local/user time !
        $output .= strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
        $output .= '<br />';
        $localtime = localtime($value,1);
        $output .= xarML('Date') . ' <select name="'.$name.'[year]"'.$id.$tabindex.'>';
        if (empty($minyear)) {
            $minyear = $localtime['tm_year'] + 1900 - 2;
        }
        if (empty($maxyear)) {
            $maxyear = $localtime['tm_year'] + 1900 + 2;
        }
        for ($i = $minyear; $i <= $maxyear; $i++) {
            if ($i == $localtime['tm_year'] + 1900) {
                $output .= '<option selected>' . $i;
            } else {
                $output .= '<option>' . $i;
            }
        }
        $output .= '</select> - <select name="'.$name.'[mon]">';
        for ($i = 1; $i <= 12; $i++) {
            if ($i == $localtime['tm_mon'] + 1) {
                $output .= '<option selected>' . $i;
            } else {
                $output .= '<option>' . $i;
            }
        }
        $output .= '</select> - <select name="'.$name.'[mday]">';
        for ($i = 1; $i <= 31; $i++) {
            if ($i == $localtime['tm_mday']) {
                $output .= '<option selected>' . $i;
            } else {
                $output .= '<option>' . $i;
            }
        }
        $output .= '</select> ';
        $output .= xarML('Time') . ' <select name="'.$name.'[hour]">';
        for ($i = 0; $i < 24; $i++) {
            if ($i == $localtime['tm_hour']) {
                $output .= '<option selected>' . sprintf("%02d",$i);
            } else {
                $output .= '<option>' . sprintf("%02d",$i);
            }
        }
        $output .= '</select> : <select name="'.$name.'[min]">';
        for ($i = 0; $i < 60; $i++) {
            if ($i == $localtime['tm_min']) {
                $output .= '<option selected>' . sprintf("%02d",$i);
            } else {
                $output .= '<option>' . sprintf("%02d",$i);
            }
        }
        $output .= '</select> : <select name="'.$name.'[sec]">';
        for ($i = 0; $i < 60; $i++) {
            if ($i == $localtime['tm_sec']) {
                $output .= '<option selected>' . sprintf("%02d",$i);
            } else {
                $output .= '<option>' . sprintf("%02d",$i);
            }
        }
        $output .= '</select> ';
        if (!empty($this->invalid)) {
            $output .= ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>';
        }
        return $output;
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is now
        if (empty($value)) {
            $value = time();
        } elseif (is_string($value)) {
            // assume dates are stored in UTC format
        // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            $value = strtotime($value);
        }
    // TODO: adapt to local/user time !
        return strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
    }

}

/**
 * Dynamic File Upload Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_FileUpload_Property extends Dynamic_Property
{
    var $size = 40;
    var $maxsize = 1000000;

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
        // FIXME : xarVarCleanFromInput() with magic_quotes_gpc On clashes with
        //         the tmp_name assigned by PHP on Windows !!!
            global $HTTP_POST_FILES;
            $file = $HTTP_POST_FILES['dd_'.$this->id];
            // is_uploaded_file() : PHP 4 >= 4.0.3
            if (is_uploaded_file($file['tmp_name']) && $file['size'] < $this->maxsize) {
                $this->value = join('', @file($file['tmp_name']));
            } else {
                $this->invalid = xarML('file upload');
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $size = 0, $maxsize = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        return '<input type="hidden" name="MAX_FILE_SIZE"'.
               ' value="'. (!empty($maxsize) ? $maxsize : $this->maxsize) .'" />' .
               '<input type="file"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
    // TODO: link to download file ?
        return '';
    }

}

/**
 * Dynamic Status Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Status_Property extends Dynamic_Select_Property
{
    function Dynamic_Status_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => 0, 'name' => xarML('Submitted')),
                                 array('id' => 1, 'name' => xarML('Rejected')),
                                 array('id' => 2, 'name' => xarML('Approved')),
                                 array('id' => 3, 'name' => xarML('Front Page')),
                             );
        }
    }

    // default showInput() from Dynamic_Select_Property

    // default showOutput() from Dynamic_Select_Property
}

/**
 * Dynamic URL Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_URL_Property extends Dynamic_TextBox_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && $value != 'http://') {
        // TODO: add some URL validation routine !
            if (preg_match('/[<>"]/',$value)) {
                $this->invalid = xarML('URL');
                $this->value = null;
                return false;
            } else {
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $size = 0, $maxlength = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = 'http://';
        }
        return '<input type="text"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($value) && $value != 'http://' ? ' [ <a href="'.$value.'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
    // TODO: use redirect function here ?
        if (!empty($value)) {
            $value = xarVarPrepForDisplay($value);
        // TODO: add alt/title here ?
            return '<a href="'.$value.'">'.$value.'</a>';
        }
        return '';
    }

}

/**
 * Dynamic Image Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Image_Property extends Dynamic_TextBox_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && $value != 'http://') {
        // TODO: add some image validation routine !
            if (preg_match('/[<>"]/',$value)) {
                $this->invalid = xarML('image URL');
                $this->value = null;
                return false;
            } else {
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null,  $size = 0, $maxlength = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = 'http://';
        }
        return '<input type="text"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($value) && $value != 'http://' ? ' [ <a href="'.$value.'" target="preview">'.xarML('show').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            $value = xarVarPrepForDisplay($value);
        // TODO: add size/alt here ?
            return '<img src="'.$value.'">';
        }
        return '';
    }

}

/**
 * Dynamic HTML Page Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_HTMLPage_Property extends Dynamic_Select_Property
{
    function Dynamic_HTMLPage_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0 && !empty($this->validation)) {
            $basedir = $this->validation;
            $filetype = 'html?';
            xarModAPILoad('dynamicdata','admin');
            $files = xarModAPIFunc('dynamicdata','admin','browse',
                                   array('basedir' => $basedir,
                                         'filetype' => $filetype));
            natsort($files);
            array_unshift($files,'');
            foreach ($files as $file) {
                $this->options[] = array('id' => $file,
                                         'name' => $file);
            }
            unset($files);
        }
    }

    // default showInput() from Dynamic_Select_Property

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        $basedir = $this->validation;
        $filetype = 'html?';
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/\\\:.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            return join('', @file($basedir.'/'.$value));
        } else {
        //    return xarVarPrepForDisplay($value);
            return '';
        }
    }

}

/**
 * Dynamic Checkbox Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Checkbox_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        // this won't do for check boxes !
        //if (!isset($value)) {
        //    $value = $this->value;
        //}
    // TODO: allow different values here, and verify $checked ?
        if (!empty($value)) {
            $this->value = 1;
        } else {
            $this->value = 0;
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        return '<input type="checkbox"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="1"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               (!empty($value) ? ' checked' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
    // TODO: allow different values here, and verify $checked ?
        if (!empty($value)) {
            return xarML('yes');
        } else {
            return xarML('no');
        }
    }

}

/**
 * Dynamic Number Box Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_NumberBox_Property extends Dynamic_TextBox_Property
{
    var $size = 10;
    var $maxlength = 30;

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $this->value = 0;
        } elseif (is_numeric($value)) {
            $this->value = intval($value);
        } else {
            $this->invalid = xarML('integer');
            $this->value = null;
            return false;
        }
        return true;
    }

    // default showInput() from Dynamic_TextBox_Property

    // default showOutput() from Dynamic_TextBox_Property
}

/**
 * Dynamic Number List Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_NumberList_Property extends Dynamic_Select_Property
{
    var $min = null;
    var $max = null;

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $this->value = 0;
        } elseif (is_numeric($value)) {
            $this->value = intval($value);
        } else {
            $this->invalid = xarML('integer');
            $this->value = null;
            return false;
        }
        if (count($this->options) == 0 && (isset($min) || isset($max)) ) {
            if ( (isset($min) && $this->value < $min) ||
                 (isset($max) && $this->value > $max) ) {
                $this->invalid = xarML('integer in range');
                $this->value = null;
                return false;
            }
        } elseif (count($this->options) > 0) {
            foreach ($this->options as $option) {
                if ($option['id'] == $this->value) {
                    return true;
                }
            }
            $this->invalid = xarML('integer in selection');
            $this->value = null;
            return false;
        } else {
            $this->invalid = xarML('integer selection');
            $this->value = null;
            return false;
        }
    }

    // default showInput() from Dynamic_Select_Property

    // default showOutput() from Dynamic_Select_Property
}

/**
 * Dynamic Number Box (float) Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_FloatBox_Property extends Dynamic_TextBox_Property
{
    var $size = 10;
    var $maxlength = 30;

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $this->value = 0;
        } elseif (is_numeric($value)) {
            $this->value = floatval($value);
        } else {
            $this->invalid = xarML('float');
            $this->value = null;
            return false;
        }
        return true;
    }

    // default showInput() from Dynamic_TextBox_Property

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && !empty($field->validation)) {
        // TODO: extract precision from field validation
            if (is_numeric($field->validation)) {
                $precision = $field->validation;
                return sprintf("%.".$precision."f",$value);
            }
        }
        return xarVarPrepForDisplay($value);
    }

}

/**
 * Dynamic Hidden Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Hidden_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('hidden field');
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }

//    function showInput($name = '', $value = null)
    function showInput($args = array())
    {
        extract($args);
        return '<input type="hidden"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' />' .
               (!empty($this->invalid) ? ' <span style="color: red">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        return '';
    }

}

/**
 * Dynamic Module Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Module_Property extends Dynamic_Select_Property
{
    function Dynamic_Module_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $modlist = xarModGetList();
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
            }
        }
    }

    // default methods from Dynamic_Select_Property
}

/**
 * Dynamic Item Type Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_ItemType_Property extends Dynamic_NumberBox_Property
{
// TODO: evaluate if we want some other output here
    // default methods from Dynamic_NumberBox_Property
}

/**
 * Dynamic Item ID Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_ItemID_Property extends Dynamic_NumberBox_Property
{
// TODO: evaluate if we want some other output here
//    function showInput($name = '', $value = null)
    function showInput($args = array())
    {
        extract($args);
        if (isset($value)) {
            return xarVarPrepForDisplay($value);
        } else {
            return xarVarPrepForDisplay($this->value);
        }
    }

    // default methods from Dynamic_NumberBox_Property
}

/**
 * Dynamic Field Type Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_FieldType_Property extends Dynamic_Select_Property
{
    function Dynamic_FieldType_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back
        // TODO: replace with something else
            $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
            foreach ($proptypes as $propid => $proptype) {
                $this->options[] = array('id' => $propid, 'name' => $proptype['label']);
            }
        }
    }

    // default methods from Dynamic_Select_Property
}

/**
 * Dynamic Data Source Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_DataSource_Property extends Dynamic_Select_Property
{
    function Dynamic_DataSource_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back
        // TODO: replace with something else
            $sources = xarModAPIFunc('dynamicdata','user','getsources');
            foreach ($sources as $source) {
                $this->options[] = array('id' => $source, 'name' => $source);
            }
        }
    }

    // default methods from Dynamic_Select_Property
}

/**
 * Dynamic Object Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Object_Property extends Dynamic_Select_Property
{
    function Dynamic_Object_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back
        // TODO: replace with something else
            $objects = xarModAPIFunc('dynamicdata','user','getobjects');
            $proptypes = Dynamic_Property_Master::getPropertyTypes();
            foreach ($objects as $objectid => $object) {
                $this->options[] = array('id' => $objectid, 'name' => $object['name']);
            }
        }
    }

    // default methods from Dynamic_Select_Property
}

/**
 * Dynamic Field Status Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_FieldStatus_Property extends Dynamic_Select_Property
{
    function Dynamic_FieldStatus_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => 0, 'name' => xarML('Disabled')),
                                 array('id' => 1, 'name' => xarML('Active')),
                                 array('id' => 2, 'name' => xarML('Display Only')),
                             );
        }
    }

    // default methods from Dynamic_Select_Property
}

?>
