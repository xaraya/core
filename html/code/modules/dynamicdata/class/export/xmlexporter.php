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

/**
 * DataObject XML Exporter
 * @todo move the xml generate code into a template based system.
 */
class XmlExporter extends DataObjectExporter
{
    public function exportObjectDef()
    {
        $objectdef = $this->getObjectDef();

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $xml = $this->addObjectDef($xml, $objectdef);

        $filename = $objectdef->properties['name']->value . '-def.xml';
        return $this->format($xml, $filename);
    }

    public function addObjectDef($xml, $objectdef)
    {
        // get the list of properties for a Dynamic Object
        $object_properties = DataPropertyMaster::getProperties(['objectid' => 1]);

        $xml .= '<object name="' . $objectdef->properties['name']->value . '">' . "\n";
        foreach (array_keys($object_properties) as $name) {
            if ($name == 'name' || !isset($objectdef->properties[$name]->value)) {
                continue;
            }
            if (is_array($objectdef->properties[$name]->value)) {
                $xml .= "  <$name>\n";
                foreach ($objectdef->$name as $field => $value) {
                    $xml .= "    <$field>" . xarVar::prepForDisplay($value) . "</$field>\n";
                }
                $xml .= "  </$name>\n";
            } elseif (in_array($name, ['access', 'config', 'sources', 'relations', 'objects', 'category'])) {
                // don't replace anything in the serialized value
                $value = $objectdef->properties[$name]->value;
                $xml .= "  <$name>" . $value . "</$name>\n";
            } else {
                $value = $objectdef->properties[$name]->value;
                $xml .= "  <$name>" . xarVar::prepForDisplay($value) . "</$name>\n";
            }
        }
        $xml = $this->addProperties($xml);
        //$xml = $this->addLinks($xml);
        $xml .= "</object>\n";

        return $xml;
    }

    public function addProperties($xml)
    {
        // get the list of properties for a Dynamic Property
        $property_properties = DataPropertyMaster::getProperties(['objectid' => 2]);

        $properties = DataPropertyMaster::getProperties(['objectid' => $this->objectid]);

        $xml .= "  <properties>\n";
        foreach (array_keys($properties) as $name) {
            $xml .= '    <property name="' . $name . '">' . "\n";
            foreach (array_keys($property_properties) as $key) {
                if ($key == 'name' || !isset($properties[$name][$key])) {
                    continue;
                }
                $val = $properties[$name][$key];
                if ($key == 'type') {
                    // replace numeric property type with text version
                    $xml .= "      <$key>" . xarVar::prepForDisplay($this->proptypes[$val]['name']) . "</$key>\n";
                } elseif ($key == 'source') {
                    // replace local table prefix with default xar_* one
                    $val = preg_replace("/^{$this->prefix}/", 'xar_', $val);
                    $xml .= "      <$key>" . xarVar::prepForDisplay($val) . "</$key>\n";
                } elseif ($key == 'configuration') {
                    // don't replace anything in the serialized value
                    $xml .= "      <$key>" . $val . "</$key>\n";
                } else {
                    $xml .= "      <$key>" . xarVar::prepForDisplay($val) . "</$key>\n";
                }
            }
            $xml .= "    </property>\n";
        }
        $xml .= "  </properties>\n";

        return $xml;
    }

    /**
     * Summary of addLinks
     * @param mixed $xml
     * @return mixed
     */
    public function addLinks($xml)
    {
        /* We don't use this
        // get object links for this object
        $name = $objectdef->properties['name']->value;
        sys::import('modules.dynamicdata.class.objects.links');
        $links = DataObjectLinks::getLinks($name,'all');
        if (!empty($links) && !empty($links[$name])) {
            $xml .= "  <links>\n";
            foreach ($links[$name] as $link) {
                $xml .= '    <link id="link_'.$link['id'].'">' . "\n";
                $xml .= '      <source>'.$link['source'].'</source>' . "\n";
                $xml .= '      <from_prop>'.$link['from_prop'].'</from_prop>' . "\n";
                $xml .= '      <link_type>'.$link['link_type'].'</link_type>' . "\n";
                $xml .= '      <target>'.$link['target'].'</target>' . "\n";
                $xml .= '      <to_prop>'.$link['to_prop'].'</to_prop>' . "\n";
                $xml .= '      <direction>'.$link['direction'].'</direction>' . "\n";
                $xml .= '    </link>' . "\n";
            }
            $xml .= "  </links>\n";
        }
        */
        return $xml;
    }

    public function exportItems()
    {
        $objectlist = $this->getObjectList();

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $xml .= "<items>\n";
        foreach ($objectlist->items as $itemid => $item) {
            $xml .= '  <' . $objectlist->name . ' itemid="' . $itemid . '">' . "\n";
            foreach ($objectlist->properties as $name => $property) {
                if (isset($item[$name]) || in_array($name, $this->deferred)) {
                    $xml .= "    <$name>";
                    $xml .= $property->exportValue($itemid, $item);
                } else {
                    $xml .= "    <$name>";
                }
                $xml .= "</$name>\n";
            }
            $xml .= '  </' . $objectlist->name . ">\n";
        }
        $xml .= "</items>\n";

        $filename = $objectlist->name . '-dat.xml';
        return $this->format($xml, $filename);
    }

    public function exportItem(int $itemid)
    {
        $objectitem = $this->getObjectItem($itemid);
        $item = $objectitem->getFieldValues();

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $xml .= '<' . $objectitem->name . ' itemid="' . $itemid . '">' . "\n";
        foreach ($objectitem->properties as $name => $property) {
            if ($property instanceof DeferredItemProperty) {
                $property->setDataToDefer($itemid, $item[$name]);
                // @checkme set the targetLoader to null to avoid retrieving the propname values
                if ($property instanceof DeferredManyProperty) {
                    $property->getDeferredLoader()->targetLoader = null;
                }
                $xml .= "  <$name>" . $property->exportValue($itemid, $item) . "</$name>\n";
            } else {
                $xml .= "  <$name>" . $property->exportValue($itemid, $item) . "</$name>\n";
            }
        }
        $xml .= '</' . $objectitem->name . ">\n";

        $filename = $objectitem->name . '-dat.' . $itemid . '.xml';
        return $this->format($xml, $filename);
    }
}
