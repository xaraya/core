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

use DataPropertyMaster;
use DeferredItemProperty;
use DeferredManyProperty;
use xarVar;
use Exception;

/**
 * DataObject JSON Exporter
 */
class JsonExporter extends DataObjectExporter
{
    public function format($info, $filename = 'export.json')
    {
        $output = json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
        $this->saveOutput($output, $filename);
        return $output;
    }

    public function exportObjectDef()
    {
        $objectdef = $this->getObjectDef();

        $info = [];
        $info = $this->addObjectDef($info, $objectdef);

        $filename = $objectdef->properties['name']->value . '-def.json';
        return $this->format($info, $filename);
    }

    public function addObjectDef($info, $objectdef)
    {
        // get the list of properties for a Dynamic Object
        $object_properties = DataPropertyMaster::getProperties(['objectid' => 1]);

        $info['@name'] = $objectdef->properties['name']->value;
        foreach (array_keys($object_properties) as $name) {
            if ($name == 'name' || !isset($objectdef->properties[$name]->value)) {
                continue;
            }
            if (is_array($objectdef->properties[$name]->value)) {
                $info[$name] = [];
                foreach ($objectdef->$name as $field => $value) {
                    $info[$name][$field] = xarVar::prepForDisplay($value);
                }
            } elseif (in_array($name, ['access', 'config', 'sources', 'relations', 'objects', 'category'])) {
                // don't replace anything in the serialized value
                $value = $objectdef->properties[$name]->value;
                if (!empty($value)) {
                    try {
                        $info[$name] = unserialize($value);
                    } catch (Exception $e) {
                        $info[$name] = $value;
                    }
                } else {
                    $info[$name] = $value;
                }
            } else {
                $value = $objectdef->properties[$name]->value;
                $info[$name] = xarVar::prepForDisplay($value);
            }
        }
        $info = $this->addProperties($info);

        return $info;
    }

    public function addProperties($info)
    {
        // get the list of properties for a Dynamic Property
        $property_properties = DataPropertyMaster::getProperties(['objectid' => 2]);

        $properties = DataPropertyMaster::getProperties(['objectid' => $this->objectid]);

        $info['properties'] = [];
        foreach (array_keys($properties) as $name) {
            $propinfo = ['@name' => $name];
            foreach (array_keys($property_properties) as $key) {
                if ($key == 'name' || !isset($properties[$name][$key])) {
                    continue;
                }
                $val = $properties[$name][$key];
                if ($key == 'type') {
                    // replace numeric property type with text version
                    $propinfo[$key] = xarVar::prepForDisplay($this->proptypes[$val]['name']);
                } elseif ($key == 'source') {
                    // replace local table prefix with default xar_* one
                    $val = preg_replace("/^{$this->prefix}/", 'xar_', $val);
                    $propinfo[$key] = xarVar::prepForDisplay($val);
                } elseif ($key == 'configuration') {
                    // don't replace anything in the serialized value
                    if (!empty($val)) {
                        try {
                            $propinfo[$key] = unserialize($val);
                        } catch (Exception $e) {
                            $propinfo[$key] = $val;
                        }
                    } else {
                        $propinfo[$key] = $val;
                    }
                } else {
                    $propinfo[$key] = xarVar::prepForDisplay($val);
                }
            }
            $info['properties'][] = $propinfo;
        }

        return $info;
    }

    public function exportItems()
    {
        $objectlist = $this->getObjectList();

        $info = [];
        foreach ($objectlist->items as $itemid => $item) {
            $iteminfo = ['@itemid' => $itemid];
            foreach ($objectlist->properties as $name => $property) {
                if (isset($item[$name]) || in_array($name, $this->deferred)) {
                    $iteminfo[$name] = $property->exportValue($itemid, $item);
                } else {
                    //$iteminfo[$name] = null;
                }
            }
            $info[] = $iteminfo;
        }

        $filename = $objectlist->name . '-dat.json';
        return $this->format($info, $filename);
    }

    public function exportItem(int $itemid)
    {
        $objectitem = $this->getObjectItem($itemid);
        $item = $objectitem->getFieldValues();

        $info = ['@itemid' => $itemid];
        foreach ($objectitem->properties as $name => $property) {
            if ($property instanceof DeferredItemProperty) {
                $property->setDataToDefer($itemid, $item[$name]);
                // @checkme set the targetLoader to null to avoid retrieving the propname values
                if ($property instanceof DeferredManyProperty) {
                    $property->getDeferredLoader()->targetLoader = null;
                }
                $info[$name] = $property->exportValue($itemid, $item);
            } else {
                $info[$name] = $property->exportValue($itemid, $item);
            }
        }

        $filename = $objectitem->name . '-dat.' . $itemid . '.json';
        return $this->format($info, $filename);
    }
}
