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

namespace Xaraya\DataObject\Export;

use DataObject;
use DataObjectDescriptor;
use DataObjectList;
use DataObjectMaster;
use BadParameterException;
use Throwable;
use xarCoreCache;
use sys;

sys::import('modules.dynamicdata.class.objects.virtual');

/**
 * DataObject PHP Class Exporter (TODO - experimental)
 */
class PhpExporter extends JsonExporter
{
    public function format($info, $filename = 'export.php')
    {
        $output = "<?php\n\$data = " . var_export($info, true) . ";\nreturn \$data;\n";
        $this->saveOutput($output, $filename);
        return $output;
    }

    public function exportObjectDef()
    {
        $objectdef = $this->getObjectDef();

        $info = '';
        $info = $this->addObjectDef($info, $objectdef);

        if ($this->tofile) {
            $filepath = dirname(__DIR__) . '/generated/' . ucwords($objectdef->name, '_') . '.php';
            file_put_contents($filepath, $info);
            $this->saveCoreCache();
        }
        return $info;
    }

    /**
     * Summary of getObjectDef
     * @throws BadParameterException
     * @return DataObject
     */
    public function getObjectDef()
    {
        // we grab the actual object here
        $myobject = DataObjectMaster::getObject([
            'objectid' => $this->objectid,
            'allprops' => true,
        ]);

        if (!isset($myobject) || empty($myobject->label)) {
            throw new BadParameterException('Invalid object id ' . $this->objectid);
        }

        return $myobject;
    }

    public function addObjectDef($info, $objectdef)
    {
        $filepath = sys::varpath() . '/cache/variables/' . $objectdef->name . '-def.php';
        static::exportDefinition($objectdef->descriptor, $filepath);

        $info .= '<?php

namespace Xaraya\DataObject\Generated;

';
        $seen = [];
        foreach ($objectdef->properties as $name => $property) {
            $classname = get_class($property);
            if (!empty($seen[$classname])) {
                continue;
            }
            $info .= "use " . $classname . ";\n";
            $seen[$classname] = true;
        }
        $classname = ucwords($objectdef->name, '_');
        $objectclass = $objectdef->descriptor->get('class') ?? 'DataObject';
        $exploded = explode('\\', $objectclass);
        $objectclass = array_pop($exploded);
        $info .= '
/**
 * Generated ' . $classname . ' class exported from DD DataObject configuration
 * with properties mapped to their ' . $objectclass . ' properties (experimental)
 *
 * Configuration saved in ' . $objectdef->name . '-def.php
 */
class ' . $classname . ' extends GeneratedClass
{
    /** @var string */
    protected static $_objectName = \'' . $objectdef->name . '\';
';
        foreach ($objectdef->properties as $name => $property) {
            $info .= "    /** @var " . get_class($property) . " */\n";
            $info .= "    public \$" . $name . ";\n";
        }

        $info .= '
    /**
     * Constructor for ' . $classname . '
     * @param ?int $itemid (optional) itemid to retrieve ' . $objectclass . ' item from database
     * @param array<string, mixed> $values (optional) values to set for ' . $objectclass . ' properties
     */
    public function __construct($itemid = null, $values = [])
    {
        parent::__construct($itemid, $values);
    }

    /**
     * Get the value of this property (= for a particular object item)
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        return parent::get($name);
    }

    /**
     * Set the value of this property (= for a particular object item)
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value = null)
    {
        parent::set($name, $value);
    }

    /**
     * Save DataObject item
     * @return int|null
     */
    public function save()
    {
        return parent::save();
    }
}
';

        return $info;
    }

    /**
     * Summary of exportDefinition
     * @param DataObjectDescriptor $descriptor
     * @param string $filepath
     * @return string
     */
    public static function exportDefinition($descriptor, $filepath)
    {
        $info = $descriptor->getArgs();
        $propertyargs = $info['propertyargs'];
        unset($info['propertyargs']);
        $arrayargs = ['access', 'config', 'sources', 'relations', 'objects', 'category'];
        foreach ($arrayargs as $name) {
            if (!empty($info[$name]) && is_string($info[$name])) {
                $info[$name] = static::tryUnserialize($info[$name]);
            }
        }
        $output = "<?php\n\n\$object = " . var_export($info, true) . ";\n";
        $output .= "\$properties = array();\n";
        foreach ($propertyargs as $propertyarg) {
            $propertyarg = array_filter($propertyarg, function ($key) {
                return !str_starts_with($key, 'object_');
            }, ARRAY_FILTER_USE_KEY);
            unset($propertyarg['_objectid']);
            if (!empty($propertyarg['configuration']) && is_string($propertyarg['configuration'])) {
                $propertyarg['configuration'] = static::tryUnserialize($propertyarg['configuration']);
            }
            $output .= "\$properties[] = " . var_export($propertyarg, true) . ";\n";
        }
        $output .= "\$object['propertyargs'] = \$properties;\nreturn \$object;\n";
        file_put_contents($filepath, $output);
        return $filepath;
    }

    public static function tryUnserialize($serialized)
    {
        try {
            $value = unserialize($serialized);
            if ($value !== false) {
                $serialized = $value;
            }
        } catch (Throwable $e) {
        }
        return $serialized;
    }

    /**
     * Summary of saveCoreCache - used in VirtualObjectDescriptor::loadCoreCache()
     * @return void
     */
    public static function saveCoreCache()
    {
        xarCoreCache::saveCached('DynamicData', 'PropertyTypes');
        xarCoreCache::saveCached('DynamicData', 'Configurations');
    }

    /**
     * Summary of unlinkObjectRef - currently not used, see tests/virtual
     * @param DataObject|DataObjectList $object
     * @return void
     */
    public static function unlinkObjectRef(& $object)
    {
        $object->datastore->object = '$this';
        //$object->datastore->db = null;
        foreach (array_keys($object->properties) as $name) {
            $object->properties[$name]->descriptor->set('objectref', '$this');
            $object->properties[$name]->objectref = '$this';
        }
    }

    /**
     * Summary of relinkObjectRef - currently not used, see tests/virtual
     * @param DataObject|DataObjectList $object
     * @return void
     */
    public static function relinkObjectRef(& $object)
    {
        //$object->descriptor->objectref = &$object;
        //$object->descriptor->set('objectref', &$object);
        $object->datastore->object = &$object;
        //$object->datastore->db = null;
        foreach (array_keys($object->properties) as $name) {
            $object->properties[$name]->descriptor->set('objectref', $object);
            $object->properties[$name]->objectref = &$object;
            if ($object instanceof DataObjectList) {
                $object->properties[$name]->_items = &$object->items;
            } else {
                $object->properties[$name]->_itemid = &$object->itemid;
            }
        }
    }
}
