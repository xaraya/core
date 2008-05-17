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
sys::import('modules.dynamicdata.class.properties.base');
sys::import('modules.dynamicdata.class.objects.base');
sys::import('modules.dynamicdata.class.properties.registration');

/**
 * Utility Class to manage Dynamic Properties
 *
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
    static function getProperties(Array $args)
    {
        // we can't use our own classes here, because we'd have an endless loop :-)

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        $dynamicprop = $xartable['dynamic_properties'];

        $bindvars = array();
        $query = "SELECT name, label, type,
                         id, defaultvalue, source,
                         status, seq, validation,
                         objectid FROM $dynamicprop ";
        if(empty($args['objectid']))
        {
            $doargs['moduleid'] = $args['moduleid'];
            $doargs['itemtype'] = $args['itemtype'];
            $info = DataObjectDescriptor::getObjectID($doargs);
        }

        $query .= " WHERE objectid = ?";
        $bindvars[] = (int) $args['objectid'];

        $anonymous = empty($args['anonymous']) ? 0 : 1;
        if(empty($args['allprops']))
            $query .= " AND status > 0 ";

        $query .= " ORDER BY seq ASC, id ASC";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $properties = array();
        while ($result->next()) {
            list(
                $name, $label, $type, $id, $defaultvalue, $source, $fieldstatus,
                $seq, $validation, $_objectid
                ) = $result->fields;
//            if (xarSecurityCheck('ReadDynamicDataField',0,'Field',"$name:$type:$id")) {
                $property = array(
                    'name'          => $name,
                    'label'         => $label,
                    'type'          => $type,
                    'id'            => $id,
                    'defaultvalue'  => $defaultvalue,
                    'source'        => $source,
                    'status'        => $fieldstatus,
                    'seq'         => $seq,
                    'validation'    => $validation,
                    // some internal variables
                    '_objectid'     => $_objectid,
                    'anonymous'     => $anonymous,
                    'class'         => ''
                );
                if(isset($args['objectref'])) {
                    self::addProperty($property,$args['objectref']);
                }
                else {
                    $properties[$name] = $property;
                }
//            }
        }
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
    static function addProperty(Array $args, &$objectref)
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

        // if the property wants a reference, give it
        if ($property->include_reference) {
            $objectref->properties[$property->name]->objectref = $objectref;
        }

        // if the property involves upload, tell its object
        if(isset($property->upload))
            $objectref->upload = true;
    }

    /**
     * Class method to get a new dynamic property of the right type
     */
    static function &getProperty(Array $args)
    {
        if(!isset($args['name']) && !isset($args['type'])) {
            throw new BadParameterException(null,xarML('The getProperty method needs either a name or type parameter.'));
        }

        if(isset($args['name']) || !is_numeric($args['type']))
        {
            // TODO: type takes precedence if it exists. should this be changed?
            if (!isset($args['type'])) if(isset($args['name'])) $args['type'] = $args['name'];
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
        } else {
            $proptypes = self::getPropertyTypes();
        }
        $clazz = 'DataProperty';
        if( isset($proptypes[$args['type']]) && is_array($proptypes[$args['type']]) )
        {
            $propertyInfo  = $proptypes[$args['type']];
            $propertyClass = $propertyInfo['class'];
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

            $clazz = $propertyClass;
        } else {
            throw new BadParameterException($args['type'], 'The dataproperty #(1) does not exist');
        }
        // Add the alias information to the class
        $args['args'] = $propertyInfo['args'];
        // DataProperty or the determined one
        $descriptor = new ObjectDescriptor($args);
        $property = new $clazz($descriptor);

        return $property;
    }

    static function createProperty(Array $args)
    {
        $descriptor = new DataObjectDescriptor(array('objectid' => 2)); // the Dynamic Properties = 2
        $object = new DataObject($descriptor);
        $objectid = $object->createItem($args);
        unset($object);
        return $objectid;
    }

    static function updateProperty(Array $args)
    {
        // TODO: what if the property type changes to something incompatible ?
    }

    static function deleteProperty(Array $args)
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
        return PropertyRegistration::Retrieve();
    }
}
?>
