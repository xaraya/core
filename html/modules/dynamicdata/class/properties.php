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
     * @param $args['objectref'] a reference to the object to add those properties to (optional)
     * @param $args['allprops'] skip disabled properties by default
     */
    function getProperties($args)
    {
        // we can't use our own classes here, because we'd have an endless loop :-)

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
        if (empty($args['allprops'])) {
            $query .= " AND xar_prop_status > 0 ";
        }
        $query .= " ORDER BY xar_prop_order ASC, xar_prop_id ASC";

        $result =& $dbconn->Execute($query);

        if (!$result) return;

        $properties = array();
        while (!$result->EOF) {
            list($name, $label, $type, $id, $default, $source, $fieldstatus, $order, $validation) = $result->fields;
			if(xarSecurityCheck('ReadDynamicDataField',1,'Field',"$name:$type:$id")) {
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
        if (!is_numeric($args['type'])) {
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
        }
        switch ($args['type'])
        {
            case 1: // (static) Static Text
                require_once "includes/properties/Dynamic_StaticText_Property.php";
                $property = new Dynamic_StaticText_Property($args);
                break;
            case 2: // (textbox) Text Box
                require_once "includes/properties/Dynamic_TextBox_Property.php";
                $property = new Dynamic_TextBox_Property($args);
                break;
            case 3: // (textarea_small) Small Text Area
                $args['rows'] = 2;
                require_once "includes/properties/Dynamic_TextArea_Property.php";
                $property = new Dynamic_TextArea_Property($args);
                break;
            case 4: // (textarea_medium) Medium Text Area
                $args['rows'] = 8;
                require_once "includes/properties/Dynamic_TextArea_Property.php";
                $property = new Dynamic_TextArea_Property($args);
                break;
            case 5: // (textarea_large) Large Text Area
                $args['rows'] = 20;
                require_once "includes/properties/Dynamic_TextArea_Property.php";
                $property = new Dynamic_TextArea_Property($args);
                break;
            case 6: // (dropdown) Dropdown List
                require_once "includes/properties/Dynamic_Select_Property.php";
                $property = new Dynamic_Select_Property($args);
                break;
            case 7: // (username) Username
                require_once "includes/properties/Dynamic_Username_Property.php";
                $property = new Dynamic_Username_Property($args);
                break;
            case 8: // (calendar) Calendar
                require_once "includes/properties/Dynamic_Calendar_Property.php";
                $property = new Dynamic_Calendar_Property($args);
                break;
            case 9: // (fileupload) File Upload
                require_once "includes/properties/Dynamic_FileUpload_Property.php";
                $property = new Dynamic_FileUpload_Property($args);
                break;
            case 10: // (status) Status
                require_once "includes/properties/Dynamic_Status_Property.php";
                $property = new Dynamic_Status_Property($args);
                break;
            case 11: // (url) URL
                require_once "includes/properties/Dynamic_URL_Property.php";
                $property = new Dynamic_URL_Property($args);
                break;
            case 12: // (image) Image
                require_once "includes/properties/Dynamic_Image_Property.php";
                $property = new Dynamic_Image_Property($args);
                break;
            case 13: // (webpage) HTML Page
                require_once "includes/properties/Dynamic_HTMLPage_Property.php";
                $property = new Dynamic_HTMLPage_Property($args);
                break;
            case 14: // (checkbox) Checkbox
                require_once "includes/properties/Dynamic_Checkbox_Property.php";
                $property = new Dynamic_Checkbox_Property($args);
                break;
            case 15: // (integerbox) Number Box
                require_once "includes/properties/Dynamic_NumberBox_Property.php";
                $property = new Dynamic_NumberBox_Property($args);
                break;
            case 16: // (integerlist) Number List
                require_once "includes/properties/Dynamic_NumberList_Property.php";
                $property = new Dynamic_NumberList_Property($args);
                break;
            case 17: // (floatbox) Number Box (float)
                require_once "includes/properties/Dynamic_FloatBox_Property.php";
                $property = new Dynamic_FloatBox_Property($args);
                break;
            case 18: // (hidden) Hidden
                require_once "includes/properties/Dynamic_Hidden_Property.php";
                $property = new Dynamic_Hidden_Property($args);
                break;
            case 19: // (module) Module
                require_once "includes/properties/Dynamic_Module_Property.php";
                $property = new Dynamic_Module_Property($args);
                break;
            case 20: // (itemtype) Item Type
                require_once "includes/properties/Dynamic_ItemType_Property.php";
                $property = new Dynamic_ItemType_Property($args);
                break;
            case 21: // (itemid) Item ID
                require_once "includes/properties/Dynamic_ItemID_Property.php";
                $property = new Dynamic_ItemID_Property($args);
                break;
            case 22: // (fieldtype) Field Type
                require_once "includes/properties/Dynamic_FieldType_Property.php";
                $property = new Dynamic_FieldType_Property($args);
                break;
            case 23: // (datasource) Data Source
                require_once "includes/properties/Dynamic_DataSource_Property.php";
                $property = new Dynamic_DataSource_Property($args);
                break;
            case 24: // (object) Object
                require_once "includes/properties/Dynamic_Object_Property.php";
                $property = new Dynamic_Object_Property($args);
                break;
            case 25: // (fieldstatus) Field Status
                require_once "includes/properties/Dynamic_FieldStatus_Property.php";
                $property = new Dynamic_FieldStatus_Property($args);
                break;
            case 26: // (email) E-Mail
                require_once "includes/properties/Dynamic_Email_Property.php";
                $property = new Dynamic_Email_Property($args);
                break;
            case 27: // (urlicon) URL Icon
                require_once "includes/properties/Dynamic_URLIcon_Property.php";
                $property = new Dynamic_URLIcon_Property($args);
                break;
            case 28: // (icq) ICQ Number
                require_once "includes/properties/Dynamic_ICQ_Property.php";
                $property = new Dynamic_ICQ_Property($args);
                break;
            case 29: // (aim) AIM Address
                require_once "includes/properties/Dynamic_AIM_Property.php";
                $property = new Dynamic_AIM_Property($args);
                break;
            case 30: // (msn) MSN Messenger
                require_once "includes/properties/Dynamic_MSN_Property.php";
                $property = new Dynamic_MSN_Property($args);
                break;
            case 31: // (yahoo) Yahoo Messenger
                require_once "includes/properties/Dynamic_Yahoo_Property.php";
                $property = new Dynamic_Yahoo_Property($args);
                break;
            case 32: // (timezone) Time Zone
                require_once "includes/properties/Dynamic_TimeZone_Property.php";
                $property = new Dynamic_TimeZone_Property($args);
                break;
            case 33: // (dateformat) Date Format
                require_once "includes/properties/Dynamic_DateFormat_Property.php";
                $property = new Dynamic_DateFormat_Property($args);
                break;
            case 34: // (radio) Radio Buttons
                require_once "includes/properties/Dynamic_RadioButtons_Property.php";
                $property = new Dynamic_RadioButtons_Property($args);
                break;
            case 35: // (imagelist) Image List
                require_once "includes/properties/Dynamic_ImageList_Property.php";
                $property = new Dynamic_ImageList_Property($args);
                break;
            case 36: // (language) Language List
                require_once "includes/properties/Dynamic_LanguageList_Property.php";
                $property = new Dynamic_LanguageList_Property($args);
                break;
            case 37: // (userlist) User List
                require_once "includes/properties/Dynamic_UserList_Property.php";
                $property = new Dynamic_UserList_Property($args);
                break;
            case 38: // (textupload) Text Upload
                // large textarea by default here
                $args['rows'] = 20;
                require_once "includes/properties/Dynamic_TextUpload_Property.php";
                $property = new Dynamic_TextUpload_Property($args);
                break;
            case 39: // (multiselect) Multi Select
                require_once "includes/properties/Dynamic_MultiSelect_Property.php";
                $property = new Dynamic_MultiSelect_Property($args);
                break;
            case 40: // (affero) Affero
                require_once "includes/properties/Dynamic_Affero_Property.php";
                $property = new Dynamic_Affero_Property($args);
                break;

            case 105: // (uploads) Upload
                require_once "includes/properties/Dynamic_Upload_Property.php";
                $property = new Dynamic_Upload_Property($args);
                break;
				
			// Using 200 range for experimental
            case 201: // (htmlarea_small) Small GUI Editor
                $args['rows'] = 2;
                require_once "includes/properties/Dynamic_HTMLArea_Property.php";
                $property = new Dynamic_HTMLArea_Property($args);
                break;
            case 202: // (htmlarea_medium) Medium GUI Editor
                $args['rows'] = 8;
                require_once "includes/properties/Dynamic_HTMLArea_Property.php";
                $property = new Dynamic_HTMLArea_Property($args);
                break;
            case 203: // (htmlarea_large) Large GUI Editor
                $args['rows'] = 20;
                $args['cols'] = 80;
                require_once "includes/properties/Dynamic_HTMLArea_Property.php";
                $property = new Dynamic_HTMLArea_Property($args);
                break;


            default:
                $property = new Dynamic_Property($args);
                break;
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

        $proptypes[26] = array(
                              'id'         => 26,
                              'name'       => 'email',
                              'label'      => 'E-Mail',
                              'format'     => '26',
                              'validation' => '',
                              // ...
                             );
        $proptypes[27] = array(
                              'id'         => 27,
                              'name'       => 'urlicon',
                              'label'      => 'URL Icon',
                              'format'     => '27',
                              'validation' => '',
                              // ...
                             );
        $proptypes[28] = array(
                              'id'         => 28,
                              'name'       => 'icq',
                              'label'      => 'ICQ Number',
                              'format'     => '28',
                              'validation' => '',
                              // ...
                             );
        $proptypes[29] = array(
                              'id'         => 29,
                              'name'       => 'aim',
                              'label'      => 'AIM Address',
                              'format'     => '29',
                              'validation' => '',
                              // ...
                             );
        $proptypes[30] = array(
                              'id'         => 30,
                              'name'       => 'msn',
                              'label'      => 'MSN Messenger',
                              'format'     => '30',
                              'validation' => '',
                              // ...
                             );
        $proptypes[31] = array(
                              'id'         => 31,
                              'name'       => 'yahoo',
                              'label'      => 'Yahoo Messenger',
                              'format'     => '31',
                              'validation' => '',
                              // ...
                             );

        $proptypes[32] = array(
                              'id'         => 32,
                              'name'       => 'timezone',
                              'label'      => 'Time Zone',
                              'format'     => '32',
                              'validation' => '',
                              // ...
                             );
        $proptypes[33] = array(
                              'id'         => 33,
                              'name'       => 'dateformat',
                              'label'      => 'Date Format',
                              'format'     => '33',
                              'validation' => '',
                              // ...
                             );
        $proptypes[34] = array(
                              'id'         => 34,
                              'name'       => 'radio',
                              'label'      => 'Radio Buttons',
                              'format'     => '34',
                              'validation' => '',
                              // ...
                             );
        $proptypes[35] = array(
                              'id'         => 35,
                              'name'       => 'imagelist',
                              'label'      => 'Image List',
                              'format'     => '35',
                              'validation' => '',
                              // ...
                             );
        $proptypes[36] = array(
                              'id'         => 36,
                              'name'       => 'language',
                              'label'      => 'Language List',
                              'format'     => '36',
                              'validation' => '',
                              // ...
                             );
        $proptypes[37] = array(
                              'id'         => 37,
                              'name'       => 'userlist',
                              'label'      => 'User List',
                              'format'     => '37',
                              'validation' => '',
                              // ...
                             );
        $proptypes[38] = array(
                              'id'         => 38,
                              'name'       => 'textupload',
                              'label'      => 'Text Upload',
                              'format'     => '38',
                              'validation' => '',
                              // ...
                             );
        $proptypes[39] = array(
                              'id'         => 39,
                              'name'       => 'multiselect',
                              'label'      => 'Multi Select',
                              'format'     => '39',
                              'validation' => '',
                              // ...
                             );
        $proptypes[40] = array(
                              'id'         => 40,
                              'name'       => 'affero',
                              'label'      => 'Affero Username',
                              'format'     => '40',
                              'validation' => '',
                              // ...
                             );
    // TODO: add multiple select and multiple checkboxes

        // add some property types supported by utility modules
        if (xarModIsAvailable('categories')) {
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
        if (xarModIsAvailable('hitcount')) {
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
        if (xarModIsAvailable('ratings')) {
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
        if (xarModIsAvailable('comments')) {
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
        if (xarModIsAvailable('comments')) {
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

	// Integrate with the uploads module for this property.
        if (xarModIsAvailable('uploads')) {
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

	// Integrate WYSIWYG Editor, if available
		if( file_exists('htmlarea/htmlarea.js') )
		{
			$proptypes[201] = array(
								  'id'         => 201,
								  'name'       => 'htmlarea_small',
								  'label'      => 'Small GUI Editor',
								  'format'     => '3',
								  'validation' => '',
								  // ...
								 );
			$proptypes[202] = array(
								  'id'         => 202,
								  'name'       => 'htmlarea_medium',
								  'label'      => 'Medium GUI Editor',
								  'format'     => '4',
								  'validation' => '',
								  // ...
								 );
			$proptypes[203] = array(
								  'id'         => 203,
								  'name'       => 'htmlarea_large',
								  'label'      => 'Large GUI Editor',
								  'format'     => '5',
								  'validation' => '',
								  // ...
								 );
		}



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

// Security Check
		if (xarSecurityCheck('ViewDynamicData',0)) {
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
}

?>
