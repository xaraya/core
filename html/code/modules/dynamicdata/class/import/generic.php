<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\Import;

use DataObject;
use DataObjectLinks;
use DataObjectMaster;
use DataPropertyMaster;
use VirtualObjectDescriptor;
use SimpleXMLElement;
use ValueValidations;
use BadParameterException;
use DuplicateException;
use EmptyParameterException;
use Exception;
use IValidation;
use xarConfigVars;
use xarDB;
use xarLog;
use xarMod;
use xarVar;
use sys;

sys::import('modules.dynamicdata.class.objects.master');
sys::import('modules.dynamicdata.class.objects.virtual');
sys::import('modules.dynamicdata.class.import.xmlimporter');
sys::import('modules.dynamicdata.class.import.jsonimporter');
sys::import('modules.dynamicdata.class.import.phpimporter');
sys::import('xaraya.validations');

/**
 * DataObject Importer
 * @todo split object and items import + support other formats besides xml
 */
class DataObjectImporter
{
    protected static ?DataObject $dataobject = null;
    protected static ?DataObject $dataproperty = null;
    /** @var array<int, mixed> */
    public array $proptypes = [];
    public string $prefix = 'xar_';
    public bool $overwrite = false;
    public bool $keepitemid = false;
    public IValidation $boolean;
    public IValidation $integer;
    /** @var array<string, mixed> */
    public array $objectcache = [];
    /** @var array<string, mixed> */
    public array $objectmaxid = [];

    /**
     * Summary of __construct
     * @param ?string $prefix
     * @param bool $overwrite
     * @param bool $keepitemid
     */
    public function __construct($prefix = null, $overwrite = false, $keepitemid = false)
    {
        $this->proptypes = DataPropertyMaster::getPropertyTypes();

        $this->prefix = $prefix ?? xarDB::getPrefix();
        $this->prefix .= '_';
        $this->overwrite = $overwrite;
        $this->keepitemid = $keepitemid;

        $this->boolean = ValueValidations::get('bool');
        $this->integer = ValueValidations::get('int');
    }

    /**
     * Import an object definition or an object item from XML
     *
     * @param array<string, mixed> $args
     * with
     *     $args['file'] location of the .xml file containing the object definition, or
     *     $args['xml'] XML string containing the object definition
     *     $args['keepitemid'] (try to) keep the item id of the different items (default false)
     *     $args['entry'] optional array of external references. (deprecated)
     * @return mixed|null object id on success, null on failure
     */
    public static function import(array $args = [])
    {
        extract($args);

        if (!isset($prefix)) {
            $prefix = xarDB::getPrefix();
        }
        $prefix .= '_';
        if (!isset($overwrite)) {
            $overwrite = false;
        }
        if (!isset($keepitemid)) {
            $keepitemid = false;
        }
        if (!isset($format)) {
            $format = 'xml';
        }

        $importer = new XmlImporter($prefix, $overwrite, $keepitemid);
        return $importer->importContent($file ?? null, $xml ?? null);
    }

    /**
     * Summary of importContent
     * @param mixed $file
     * @param mixed $xml
     * @throws \EmptyParameterException
     * @throws \BadParameterException
     * @return mixed
     */
    public function importContent($file = null, $xml = null)
    {
        if (empty($xml) && empty($file)) {
            throw new EmptyParameterException('xml or file');
        } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/', $file))) {
            // check if we tried to load a file using an old path
            if (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true && strpos($file, 'modules/') === 0) {
                $file = sys::code() . $file;
                if (!file_exists($file)) {
                    throw new BadParameterException($file, 'Invalid importfile "#(1)"');
                }
            } else {
                throw new BadParameterException($file, 'Invalid importfile "#(1)"');
            }
        }

        if (!empty($file)) {
            $xml = file_get_contents($file);
            xarLog::message(xarML('DD: Importing file #(1)', $file), xarLog::LEVEL_INFO);

        } elseif (!empty($xml)) {
            // remove garbage from the end
            $xml = preg_replace('/>[^<]+$/s', '>', $xml);
        } else {
            return null;
        }

        $objectid = 0;
        if (str_contains($xml, '<object name=')) {

            $objectid = $this->importObjectDef($xml);

        } elseif (str_contains($xml, '<items>')) {

            $objectid = $this->importItems($xml);

        }
        return $objectid;
    }

    /**
     * Summary of importObjectDef
     * @param mixed $xml
     * @throws \DuplicateException
     * @throws \BadParameterException
     * @return int|mixed
     */
    public function importObjectDef($xml)
    {
        $xmlobject = new SimpleXMLElement($xml);
        $objectid = 0;

        # --------------------------------------------------------
        #
        # Process an object definition (-def.xml file)
        #
        //FIXME: this unconditionally CLEARS the incoming parameter!!
        $args = [];
        // Get the object's name
        $args['name'] = (string)($xmlobject->attributes()->name);
        xarLog::message('DD: importing ' . $args['name'], xarLog::LEVEL_INFO);

        // check if the object exists
        $info = DataObjectMaster::getObjectInfo(['name' => $args['name']]);
        $dupexists = !empty($info);
        if ($dupexists && !$this->overwrite) {
            //$msg = 'Duplicate definition for #(1) #(2)';
            //$vars = ['object',xarVar::prepForDisplay($args['name'])];
            throw new DuplicateException(null, $args['name']);
        }

        $object = DataObjectMaster::getObject(['name' => 'objects']);
        $objectproperties = array_keys($object->properties);
        foreach($objectproperties as $property) {
            if (isset($xmlobject->{$property}[0])) {
                $value = (string)$xmlobject->{$property}[0];
                try {
                    $this->boolean->validate($value, []);
                } catch (Exception $e) {
                    try {
                        $this->integer->validate($value, []);
                    } catch (Exception $e) {
                    }
                }

                $args[$property] = $value;
            }
        }
        // Backwards Compatibility with old definitions
        if (empty($args['moduleid']) && !empty($args['module_id'])) {
            $args['moduleid'] = $args['module_id'];
        }
        if (empty($args['name']) || empty($args['moduleid'])) {
            throw new BadParameterException(null, 'Missing keys in object definition');
        }
        // Make sure we drop the object id, because it might already exist here
        //TODO: don't define it in the first place?
        unset($args['objectid']);

        // Add an item to the object
        $args['itemtype'] = xarMod::apiFunc(
            'dynamicdata',
            'admin',
            'getnextitemtype',
            ['module_id' => $args['moduleid']]
        );

        // Create the DataProperty object we will use to create items of
        $dataproperty = DataObjectMaster::getObject(['name' => 'properties']);
        if (empty($dataproperty)) {
            return null;
        }

        if ($dupexists && $this->overwrite) {
            $args['itemid'] = $info['objectid'];
            $args['itemtype'] = $info['itemtype'];
            // Load the object properties directly with the values to bypass their setValue methods
            $object->setFieldValues($args, 1);
            $objectid = $object->updateItem(['itemid' => $args['itemid']]);
            $objectid = $object->updateItem();
            // remove the properties, as they will be replaced
            $duplicateobject = DataObjectMaster::getObject(['name' => $info['name']]);
            $oldproperties = $duplicateobject->properties;
            foreach ($oldproperties as $propertyitem) {
                $dataproperty->deleteItem(['itemid' => $propertyitem->id]);
            }
        } else {
            // Load the object properties directly with the values to bypass their setValue methods
            $object->setFieldValues($args, 1);
            $objectid = $object->createItem();
        }

        # --------------------------------------------------------
        #
        # Now process the objects's properties
        #
        // @checkme if you need to import new property types as part of module install to create objects,
        // please have a look at ./modules/class/eventobservers/modactivate.php and
        // ./modules/class/installer.php - properties are only imported during activate now, not at install
        // Don't use $proptypes = PropertyRegistration::importPropertyTypes(); here since this would be
        // called again & again when creating every object in Xaraya for core, modules, tables, ...
        $proptypes = DataPropertyMaster::getPropertyTypes();
        $name2id = [];
        foreach ($proptypes as $propid => $proptype) {
            $name2id[$proptype['name']] = $propid;
        }

        $propertyproperties = array_keys($dataproperty->properties);
        $propertieshead = $xmlobject->properties;
        foreach($propertieshead->children() as $property) {
            $propertyargs = [];
            $propertyname = (string)($property->attributes()->name);
            $propertyargs['name'] = $propertyname;
            foreach($propertyproperties as $prop) {
                if (isset($property->{$prop}[0])) {
                    $value = (string)$property->{$prop}[0];
                    try {
                        $this->boolean->validate($value, []);
                    } catch (Exception $e) {
                        try {
                            $this->integer->validate($value, []);
                        } catch (Exception $e) {
                        }
                    }
                    $propertyargs[$prop] = $value;
                }
            }

            // Backwards Compatibility with old definitions
            if (!isset($propertyargs['defaultvalue']) && isset($property->{'default'}[0])) {
                $propertyargs['defaultvalue'] = (string)$property->{'default'}[0];
            }
            if (!isset($propertyargs['seq']) && isset($property->{'order'}[0])) {
                $propertyargs['seq'] = (int)$property->{'order'}[0];
            }
            if (!isset($propertyargs['configuration']) && isset($property->{'validation'}[0])) {
                $propertyargs['configuration'] = (string)$property->{'validation'}[0];
            }

            // Add some args needed to define the property
            unset($propertyargs['id']);
            $propertyargs['objectid'] = $objectid;
            $propertyargs['itemid']   = 0;

            // Now do some checking
            if (empty($propertyargs['name']) || empty($propertyargs['type'])) {
                throw new BadParameterException(null, 'Missing keys in property definition');
            }
            // convert property type to numeric if necessary
            if (!is_numeric($propertyargs['type'])) {
                if (isset($name2id[$propertyargs['type']])) {
                    $propertyargs['type'] = $name2id[$propertyargs['type']];
                } else {
                    $propertyargs['type'] = 1;
                }
            }
            // TODO: watch out for multi-sites
            // replace default xar_* table prefix with local one
            if (!empty($propertyargs['source'])) {
                $propertyargs['source'] = preg_replace("/^xar_/", $this->prefix, $propertyargs['source']);
            } else {
                $propertyargs['source'] = "";
            }

            // Force a new itemid to be created for this property
            $dataproperty->properties[$dataproperty->primary]->setValue(0);
            // Create the property
            $id = $dataproperty->createItem($propertyargs);
        }

        if (!empty($xmlobject->links)) {
            // make sure that object links are initialized
            sys::import('modules.dynamicdata.class.objects.links');
            $linklist = DataObjectLinks::initLinks();
            if (empty($linklist)) {
                // no object links initialized, bail out
                return $objectid;
            }
            $linkshead = $xmlobject->links;
            $linkprops = ['source','from_prop','target','to_prop','link_type','direction'];
            foreach ($linkshead->children() as $link) {
                $info = [];
                foreach ($linkprops as $prop) {
                    if (!isset($link->{$prop}[0])) {
                        unset($info);
                        break;
                    }
                    $info[$prop] = (string)$link->{$prop}[0];
                }
                if (!empty($info)) {
                    // add this link and its reverse if it doesn't exist yet
                    DataObjectLinks::addLink($info['source'], $info['from_prop'], $info['target'], $info['to_prop'], $info['link_type'], $info['direction']);
                }
            }
        }

        return $objectid;
    }

    /**
     * Summary of importItems
     * @param mixed $xml
     * @throws \Exception
     * @return mixed
     */
    public function importItems($xml)
    {
        $xmlobject = new SimpleXMLElement($xml);
        $objectid = 0;
        # --------------------------------------------------------
        #
        # Process an object's items (-dat.xml file)
        #
        $currentobject = "";
        $index = 1;
        $count = count($xmlobject->children());

        // pass on a generic value so that the class(es) will know where we are
        $args['dd_import'] = true;

        $object = null;
        $objectproperties = [];
        foreach($xmlobject->children() as $child) {

            // pass on some generic values so that the class(es) will know where we are
            if ($index == 1) {
                $args['dd_position'] = 'first';
            } elseif ($index == $count) {
                $args['dd_position'] = 'last';
            } else {
                $args['dd_position'] = '';
            }
            $index += 1;

            $thisname = $child->getName();
            $args['itemid'] = (!empty($this->keepitemid)) ? (string)$child->attributes()->itemid : 0;

            // set up the object the first time around in this loop
            if ($thisname != $currentobject) {
                if (!empty($currentobject)) {
                    throw new Exception("The items imported must all belong to the same object");
                }
                $currentobject = $thisname;

                /*
                // Check that this is a real object
                if (empty($objectnamelist[$currentobject])) {
                    $objectinfo = DataObjectMaster::getObjectInfo(array('name' => $currentobject));
                    if (isset($objectinfo) && !empty($objectinfo['objectid'])) {
                        $objectname2objectid[$currentobject] = $$currentobject;
                    } else {
                        $msg = 'Unknown #(1) "#(2)"';
                        $vars = array('object',xarVar::prepForDisplay($thisname));
                        throw new BadParameterException($vars,$msg);
                    }
                }
                */
                // Create the item
                if (!isset($this->objectcache[$currentobject])) {
                    $this->objectcache[$currentobject] = DataObjectMaster::getObject(['name' => $currentobject]);
                }
                /** @var DataObject $object */
                $object = $this->objectcache[$currentobject];
                $objectid = $this->objectcache[$currentobject]->objectid;
                // Get the properties for this object
                $objectproperties = $object->properties;
            }

            $oldindex = 0;
            foreach($objectproperties as $propertyname => $property) {
                if (isset($child->$propertyname)) {
                    // Run the import value through the property's validation routine
                    //$check = $property->validateValue((string)$child->$propertyname);
                    $value = $property->importValue($child);
                    //                    $value = (string)$child->$propertyname;
                    try {
                        $this->boolean->validate($value, []);
                    } catch (Exception $e) {
                        try {
                            $this->integer->validate($value, []);
                        } catch (Exception $e) {
                        }
                    }
                    $object->properties[$propertyname]->value = $value;
                }
            }
            if (empty($this->keepitemid)) {
                // for dynamic objects, set the primary field to 0 too
                if (isset($object->primary)) {
                    $primary = $object->primary;
                    if (!empty($object->properties[$primary]->value)) {
                        $object->properties[$primary]->value = 0;
                    }
                }
            }

            // for the moment we only allow creates
            // create the item
            $itemid = $object->createItem($args);
            if (empty($itemid)) {
                return;
            }

            // keep track of the highest item id
            //if (empty($this->objectmaxid[$currentobject]) || $this->objectmaxid[$currentobject] < $itemid) {
            //    $this->objectmaxid[$currentobject] = $itemid;
            //}
        }

        /* don't think this is needed atm
        // adjust maxid (for objects stored in the dynamic_data table)
        if (count($this->objectcache) > 0 && count($this->objectmaxid) > 0) {
            foreach (array_keys($this->objectcache) as $objectname) {
                if (!empty($this->objectmaxid[$objectname]) && $object->maxid < $this->objectmaxid[$objectname]) {
                    $itemid = DataObjectMaster::updateObject(array('name' => $objectname,
                                                                    'maxid'    => $this->objectmaxid[$objectname]));
                    if (empty($itemid)) return;
                }
            }
            $this->objectcache = [];
        }
        */
        return $objectid;
    }

    /**
     * Summary of createObject
     * @param VirtualObjectDescriptor $descriptor
     * @return int|mixed
     */
    public static function createObject($descriptor)
    {
        static::$dataobject ??= DataObjectMaster::getObject(['name' => 'objects']);
        static::$dataproperty ??= DataObjectMaster::getObject(['name' => 'properties']);
        $info = $descriptor->getArgs();
        $propertyargs = $info['propertyargs'];
        unset($info['propertyargs']);
        $objectid = static::$dataobject->createItem($info);
        $sequence = 1;
        foreach ($propertyargs as $propertyarg) {
            $propertyarg = array_filter($propertyarg, function ($key) {
                return !str_starts_with($key, 'object_');
            }, ARRAY_FILTER_USE_KEY);
            $propertyarg['itemid'] = 0;
            $propertyarg['objectid'] = $objectid;
            unset($propertyarg['_objectid']);
            $propertyarg['seq'] ??= $sequence;
            $propertyarg['configuration'] ??= '';
            $propid = static::$dataproperty->createItem($propertyarg);
            $sequence += 1;
        }
        return $objectid;
    }
}
