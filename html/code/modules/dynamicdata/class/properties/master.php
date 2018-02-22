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

// this is used in most methods below, so we import it here
sys::import('modules.dynamicdata.class.objects.descriptor');

/**
 * Utility Class to manage Dynamic Properties
 *
 */
class DataPropertyMaster extends Object
{
    const DD_DISPLAYSTATE_DISABLED = 0;
    const DD_DISPLAYSTATE_ACTIVE = 1;
    const DD_DISPLAYSTATE_DISPLAYONLY = 2;
    const DD_DISPLAYSTATE_HIDDEN = 3;
    const DD_DISPLAYSTATE_VIEWONLY = 4;

    const DD_INPUTSTATE_ADDMODIFY = 32;
    const DD_INPUTSTATE_NOINPUT = 64;
    const DD_INPUTSTATE_ADD = 96;
    const DD_INPUTSTATE_MODIFY = 128;
    const DD_INPUTSTATE_IGNORED = 160;

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
    static function getProperties(Array $args=array())
    {
        // we can't use our own classes here, because we'd have an endless loop :-)

        $dbconn = xarDB::getConn();
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartable =& xarDB::getTables();

        $dynamicprop = $xartable['dynamic_properties'];

        $bindvars = array();
        $query = "SELECT name, label, type,
                         id, defaultvalue, source,
                         status, translatable, seq, configuration,
                         object_id FROM $dynamicprop ";
        if(empty($args['objectid']))
        {
            if (empty($args['moduleid'])) throw new EmptyParameterException('moduleid');
            if (empty($args['itemtype'])) throw new EmptyParameterException('itemtype');
            $doargs['moduleid'] = $args['moduleid'];
            $doargs['itemtype'] = $args['itemtype'];
            $info = DataObjectDescriptor::getObjectID($doargs);
        }

        $query .= " WHERE object_id = ?";
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
                $name, $label, $type, $id, $defaultvalue, $source, $status, $translatable,
                $seq, $configuration, $_objectid
                ) = $result->fields;
            $property = array(
                'name'          => $name,
                'label'         => $label,
                'type'          => $type,
                'id'            => $id,
                'defaultvalue'  => $defaultvalue,
                'source'        => $source,
                'status'        => $status,
                'translatable'  => $translatable,
                'seq'           => $seq,
                'configuration' => $configuration,
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

        xarLog::message("DataPropertyMaster::addProperty: Adding a new property " . $args['name'], xarLog::LEVEL_INFO);
        
        // "beautify" label based on name if not specified
        // TODO: this is a presentation issue, doesnt belong here.
        if(!isset($args['label']) && !empty($args['name']))
        {
            $args['label'] = strtr($args['name'], '_', ' ');
            $args['label'] = ucwords($args['label']);
        }

        // For now, always add a reference to the parent object
        $args['objectref'] = $objectref;

        // Get a new property
        $property =& self::getProperty($args);

        if(method_exists($objectref, 'getItems'))
            // for dynamic object lists, put a reference to the $items array in the property
            $property->_items =& $objectref->items;
        elseif(method_exists($objectref, 'getItem'))
            // for dynamic objects, put a reference to the $itemid value in the property
            $property->_itemid =& $objectref->itemid;

        // add it to the list of properties
        $objectref->properties[$property->name] =& $property;

        // Expose the object configuration to the property
        $objectref->properties[$property->name]->objectconfiguration =& $objectref->configuration;

        // if the property involves upload, tell its object
        if(isset($property->upload)) $objectref->upload = true;
        
        return true;
    }

    /**
     * Class method to get a new dynamic property of the right type
     */
    static function &getProperty(Array $args=array())
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
        if (!class_exists('DataProperty')) {
            sys::import('modules.dynamicdata.class.properties.base');
        }
        $clazz = 'DataProperty';
        if( isset($proptypes[$args['type']]) && is_array($proptypes[$args['type']]) )
        {
            $propertyInfo  = $proptypes[$args['type']];
            $propertyClass = $propertyInfo['class'];

            xarLog::message("DataPropertyMaster::getProperty: Getting a new property " . $propertyClass, xarLog::LEVEL_INFO);

            // If we don't have the class yet, get it now
            if (!class_exists($propertyClass)) {
                
                // Make sure we have a property PHP file
                $propertyfile = sys::code() . $propertyInfo['filepath'];
                if(!file_exists($propertyfile)) throw new FileNotFoundException($propertyfile);
                    
                // Import the file to get the property's class
                $dp = str_replace('/','.',substr($propertyInfo['filepath'],0,-4)); // minus .php
                sys::import($dp);
                
                // Load the translations for this file
                $loaded = xarMLS::loadTranslations($propertyfile);
                if (!$loaded) xarLog::message("Property translations for $propertyClass NOT loaded", xarLog::LEVEL_WARNING);
            }

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
    static function createProperty(Array $args=array())
    {
        $object = DataObjectMaster::getObject(
                                        array(
                                            'name' => 'properties',
                                            'itemid'   => $args['itemid']
                                        )
                                    );
        $objectid = $object->createItem($args);
        unset($object);
        return $objectid;
    }

    static function updateProperty(Array $args=array())
    {
        // TODO: what if the property type changes to something incompatible ?
    }

    static function deleteProperty(Array $args=array())
    {
        if(empty($args['itemid']))
            return;

        // TODO: delete all the (dynamic ?) data for this property as well
        $object = DataObjectMaster::getObject(
                                        array(
                'name'   => 'properties', // the Dynamic Properties = 2
                'itemid' => $args['itemid']
                                        )
                                    );
        if (!class_exists('DataObject')) {
            sys::import('modules.dynamicdata.class.objects.base');
        }
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
        if (!class_exists('PropertyRegistration')) {
            sys::import('modules.dynamicdata.class.properties.registration');
        }
        return PropertyRegistration::Retrieve();
    }

    /**
     * Class method to check if a property is available
     */
    static function isAvailable($name=null)
    {
        if (empty($name)) return false;
        $types= self::getPropertyTypes();
        foreach ($types as $type) {
            if ($type['name'] == $name) return true;
        }
        return false;
    }
}
?>