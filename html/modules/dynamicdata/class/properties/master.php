<?php

sys::import('modules.dynamicdata.class.properties.base');
sys::import('modules.dynamicdata.class.objects.base');
sys::import('modules.dynamicdata.class.properties.registration');

/**
 * Utility Class to manage Dynamic Properties
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class DataPropertyMaster extends Object
{
    const DD_DISPLAYSTATE_DISABLED = 0;
    const DD_DISPLAYSTATE_HIDDEN = 3;
    const DD_DISPLAYSTATE_DISPLAYONLY = 2;
    const DD_DISPLAYSTATE_ACTIVE = 1;

    const DD_INPUTSTATE_ADDMODIFY = 32;
    const DD_INPUTSTATE_NOINPUT = 64;
    const DD_INPUTSTATE_ADD = 96;
    const DD_INPUTSTATE_MODIFY = 128;

    const DD_DISPLAYMASK = 31;

    /**
     * Get the dynamic properties of an object
     *
     * @param $args['objectid'] the object id of the object, or
     * @param $args['moduleid'] the module id of the object +
     * @param $args['itemtype'] the itemtype of the object
     * @param $args['objectref'] a reference to the object to add those properties to (optional)
     * @param $args['allprops'] skip disabled properties by default
     */
    static function getProperties(array $args)
    {
        // we can't use our own classes here, because we'd have an endless loop :-)

        $dbconn = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicprop = $xartable['dynamic_properties'];

        $bindvars = array();
        $query = "SELECT xar_prop_name, xar_prop_label, xar_prop_type,
                         xar_prop_id, xar_prop_default, xar_prop_source,
                         xar_prop_status, xar_prop_order, xar_prop_validation,
                         xar_prop_objectid, xar_prop_moduleid, xar_prop_itemtype
                  FROM $dynamicprop ";
        if(isset($args['objectid']))
        {
            $query .= " WHERE xar_prop_objectid = ?";
            $bindvars[] = (int) $args['objectid'];
        }
        else
        {
            $query .= " WHERE xar_prop_moduleid = ?
                          AND xar_prop_itemtype = ?";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }
        if(empty($args['allprops']))
            $query .= " AND xar_prop_status > 0 ";

        $query .= " ORDER BY xar_prop_order ASC, xar_prop_id ASC";

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $properties = array();
        while ($result->next())
        {
            list(
                $name, $label, $type, $id, $default, $source, $fieldstatus,
                $order, $validation, $_objectid, $_moduleid, $_itemtype
            ) = $result->fields;
            if(xarSecurityCheck('ReadDynamicDataField',0,'Field',"$name:$type:$id"))
            {
                $property = array(
                    'name'          => $name,
                    'label'         => $label,
                    'type'          => $type,
                    'id'            => $id,
                    'default'       => $default,
                    'source'        => $source,
                    'status'        => $fieldstatus,
                    'order'         => $order,
                    'validation'    => $validation,
                    // some internal variables
                    '_objectid'     => $_objectid,
                    '_moduleid'     => $_moduleid,
                    '_itemtype'     => $_itemtype
                );
                if(isset($args['objectref']))
                    self::addProperty($property,$args['objectref']);
                else
                    $properties[$name] = $property;
            }
        }
//        $result->close();

        return $properties;
    }

    /**
     * Add a dynamic property to an object
     *
     * @param $args['name'] the name for the dynamic property
     * @param $args['type'] the type of dynamic property
     * @param $args['label'] the label for the dynamic property
     * @param $args['source'] the source for the dynamic property
     * @param $args['datastore'] the datastore for the dynamic property
     * ...
     * @param $objectref a reference to the object to add this property to
     * @todo  this look like it needs to be in object class
     * @todo  if not, we should define an interface for D_Obj and D_Obj_List so we can type hint on it
     */
    static function addProperty(array $args, &$objectref)
    {
        if(!isset($objectref) || empty($args['name']) || empty($args['type']))
            return;

        // "beautify" label based on name if not specified
        // TODO: this is a presentation issue, doesnt belong here.
        if(!isset($args['label']) && !empty($args['name']))
        {
            $args['label'] = strtr($args['name'], '_', ' ');
            $args['label'] = ucwords($args['label']);
        }

        // get a new property
        $property =& self::getProperty($args);

        // for dynamic object lists, put a reference to the $items array in the property
        if(method_exists($objectref, 'getItems'))
            $property->_items =& $objectref->items;
        elseif(method_exists($objectref, 'getItem'))
            // for dynamic objects, put a reference to the $itemid value in the property
            $property->_itemid =& $objectref->itemid;

        // add it to the list of properties
        $objectref->properties[$property->name] =& $property;

        if(isset($property->upload))
            $objectref->upload = true;
    }

    /**
     * Class method to get a new dynamic property of the right type
     */
    static function &getProperty(array $args)
    {
        if(!is_numeric($args['type']))
        {
            $proptypes = self::getPropertyTypes();
            if(!isset($proptypes))
                $proptypes = array();

            foreach ($proptypes as $typeid => $proptype)
            {
                if($proptype['name'] == $args['type'])
                {
                    $args['type'] = $typeid;
                    break;
                }
            }
        }
        else
            $proptypes = self::getPropertyTypes();

        $clazz = 'DataProperty';
        if( isset($proptypes[$args['type']]) && is_array($proptypes[$args['type']]) )
        {
            $propertyInfo  = $proptypes[$args['type']];
            $propertyClass = $propertyInfo['propertyClass'];
            // Filepath is complete real path to the php file, and decoupled from the class name
            // We should load the MLS translations for the right context here, in case the property
            // PHP file contains xarML() statements
            // See bug 5097
            if(preg_match('/modules\/(.*)\/xarproperties/',$propertyInfo['filepath'],$matches) == 1)
            {
                // @todo: The preg determines the module name (in a sloppy way, FIX this)
                // @todo: do we still do properties from includes/properties?
                xarMLSLoadTranslations($propertyInfo['filepath']);
            }
            else
                xarLogMessage("WARNING: Property translations for $propertyClass NOT loaded");

            if(!file_exists($propertyInfo['filepath']))
                throw new FileNotFoundException($propertyInfo['filepath']);

            $dp = str_replace('/','.',substr($propertyInfo['filepath'],0,-4)); // minus .php
            sys::import($dp);

            if( isset($propertyInfo['args']) && ($propertyInfo['args'] != '') )
            {
                $baseArgs = unserialize($propertyInfo['args']);
                $args = array_merge($baseArgs, $args);
            }
            $clazz = $propertyClass;
        }
        // DataProperty or the determined one
        $descriptor = new ObjectDescriptor($args);
        $property = new $clazz($descriptor);

        return $property;
    }

    static function createProperty(array $args)
    {
        $descriptor = new DataObjectDescriptor(array('objectid' => 2)); // the Dynamic Properties = 2
        $object = new DataObject($descriptor);
        $objectid = $object->createItem($args);
        unset($object);
        return $objectid;
    }

    static function updateProperty(array $args)
    {
        // TODO: what if the property type changes to something incompatible ?
    }

    static function deleteProperty(array $args)
    {
        if(empty($args['itemid']))
            return;

        // TODO: delete all the (dynamic ?) data for this property as well
        $descriptor = new DataObjectDescriptor(
            array(
                'objectid' => 2, // the Dynamic Properties = 2
                'itemid'   => $args['itemid']
            )
        );
        $object = new DataObject($descriptor);
        if(empty($object))
            return;

        $objectid = $object->getItem();
        if(empty($objectid))
            return;

        $objectid = $object->deleteItem();
        unset($object);
        return $objectid;
    }

    /**
     * Class method listing all defined property types
     */
    static function getPropertyTypes()
    {
        if(xarVarIsCached('DynamicData','PropertyTypes')) {
            return xarVarGetCached('DynamicData','PropertyTypes');
        }

        // Attempt to retrieve properties from DB
        $property_types = PropertyRegistration::Retrieve();

        /*
         // Security Check
         if(xarSecurityCheck('ViewDynamicData',0)) {
             $proptypes[] = array(...);
         }
         }
        */
        xarVarSetCached('DynamicData','PropertyTypes',$property_types);
        return $property_types;
    }

}
?>