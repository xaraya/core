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
use DataObjectList;
use DataObjectMaster;
use DataPropertyMaster;
use xarCoreCache;
use sys;

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
     * @return DataObject|void
     */
    public function getObjectDef()
    {
        // we grab the actual object here
        $myobject = DataObjectMaster::getObject([
            'objectid' => $this->objectid,
            'allprops' => true,
        ]);

        if (!isset($myobject) || empty($myobject->label)) {
            return;
        }

        return $myobject;
    }

    public function addObjectDef($info, $objectdef)
    {
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
        $info .= '
class ' . ucwords($objectdef->name, '_') . ' extends GeneratedClass {
';
        foreach ($objectdef->properties as $name => $property) {
            $info .= "    /** @var " . get_class($property) . " */\n";
            $info .= "    public \$" . $name . ";\n";
        }

        $args = $objectdef->descriptor->getArgs();
        $propertyargs = $args['propertyargs'];
        unset($args['propertyargs']);
        $info .= '
    /** @var array<string, mixed> */
    protected static $_descriptorArgs = ' . str_replace("\n", "\n    ", var_export($args, true)) . ';
    /** @var list<array<string, mixed>> */
    protected static $_propertyArgs = array (
';
        foreach ($propertyargs as $propertyarg) {
            if (!DataPropertyMaster::isPropertyEnabled($propertyarg)) {
                continue;
            }
            $propertyarg = array_filter($propertyarg, function ($key) {
                return !str_starts_with($key, 'object_');
            }, ARRAY_FILTER_USE_KEY);
            unset($propertyarg['_objectid']);
            $info .= "        " . str_replace("\n", "\n        ", var_export($propertyarg, true)) . ",\n";
        }
        $info .= '
    );

    /**
     * Get the value of this property (= for a particular object item)
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        // don\'t use the property getValue() here
        //return $this->$name->getValue();
        return $this->_values[$name] ?? null;
    }

    /**
     * Set the value of this property (= for a particular object item)
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value = null)
    {
        // use the property setValue() and getValue() here
        $this->$name->setValue($value);
        $this->_values[$name] = $this->$name->getValue();
    }
}
';

        return $info;
    }

    /**
     * Summary of saveCoreCache
     * @return void
     */
    public function saveCoreCache()
    {
        $filepath = sys::varpath() . '/cache/variables/DynamicData.PropertyTypes.php';
        $proptypes = xarCoreCache::getCached('DynamicData', 'PropertyTypes');
        $info = '<?php
$proptypes = ' . var_export($proptypes, true) . ';
//xarCoreCache::setCached("DynamicData", "PropertyTypes", $proptypes);
return $proptypes;
';
        file_put_contents($filepath, $info);
        $filepath = sys::varpath() . '/cache/variables/DynamicData.Configurations.php';
        $configprops = xarCoreCache::getCached('DynamicData', 'Configurations');
        $info = '<?php
$configprops = ' . var_export($configprops, true) . ';
//xarCoreCache::setCached("DynamicData", "Configurations", $configprops);
return $configprops;
';
        file_put_contents($filepath, $info);
    }

    /**
     * Summary of unlinkObjectRef
     * @param DataObject|DataObjectList $object
     * @return void
     */
    public function unlinkObjectRef(& $object)
    {
        $object->datastore->object = '$this';
        //$object->datastore->db = null;
        foreach (array_keys($object->properties) as $name) {
            $object->properties[$name]->descriptor->set('objectref', '$this');
            $object->properties[$name]->objectref = '$this';
        }
    }

    /**
     * Summary of relinkObjectRef
     * @param DataObject|DataObjectList $object
     * @return void
     */
    public function relinkObjectRef(& $object)
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
