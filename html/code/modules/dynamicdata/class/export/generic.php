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
use DeferredItemProperty;
use DeferredManyProperty;
use xarDB;
use sys;

sys::import('modules.dynamicdata.class.export.xmlexporter');
sys::import('modules.dynamicdata.class.export.jsonexporter');
sys::import('modules.dynamicdata.class.export.phpexporter');

/**
 * DataObject Exporter
 */
class DataObjectExporter
{
    /** @var array<string> */
    public array $deferred = [];
    /** @var array<int, mixed> */
    public array $proptypes = [];
    public string $prefix = 'xar_';

    public function __construct(public int $objectid)
    {
    }

    /**
     * Summary of exportObjectDef
     * @return string
     */
    public function exportObjectDef()
    {
        $objectdef = $this->getObjectDef();

        $output = '';
        $output = $this->addObjectDef($output, $objectdef);

        return $this->format($output);
    }

    /**
     * Summary of addObjectDef
     * @param mixed $output
     * @param mixed $objectdef
     * @return mixed
     */
    public function addObjectDef($output, $objectdef)
    {
        return $output;
    }

    /**
     * Summary of addProperties
     * @param mixed $output
     * @return mixed
     */
    public function addProperties($output)
    {
        return $output;
    }

    /**
     * Summary of exportItems
     * @return string
     */
    public function exportItems()
    {
        $objectlist = $this->getObjectList();
        $output = '';
        return $this->format($output);
    }

    /**
     * Summary of exportItem
     * @param int $itemid
     * @return string
     */
    public function exportItem(int $itemid)
    {
        $objectitem = $this->getObjectItem($itemid);
        $item = $objectitem->getFieldValues();
        $output = '';
        return $this->format($output);
    }

    /**
     * Summary of format
     * @param mixed $output
     * @return mixed
     */
    public function format($output)
    {
        return $output;
    }

    /**
     * Summary of getObjectDef
     * @return DataObject|void
     */
    public function getObjectDef()
    {
        $myobject = DataObjectMaster::getObject(['name' => 'objects']);

        $myobject->getItem(['itemid' => $this->objectid]);

        if (!isset($myobject) || empty($myobject->label) || empty($myobject->properties['objectid']->value)) {
            return;
        }

        $this->proptypes = DataPropertyMaster::getPropertyTypes();

        $this->prefix = xarDB::getPrefix();
        $this->prefix .= '_';

        return $myobject;
    }

    /**
     * Summary of getObjectList
     * @return DataObjectList|null
     */
    public function getObjectList()
    {
        $mylist = DataObjectMaster::getObjectList([
            'objectid' => $this->objectid,
            'prelist'  => false,
        ]);     // don't run preList method

        // Export all properties that are not disabled
        foreach ($mylist->properties as $name => $property) {
            $status = $property->getDisplayStatus();
            if ($status == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) {
                // Remove this property if it is disabled
                unset($mylist->properties[$name]);
            } else {
                // Anything else: set to active
                $mylist->properties[$name]->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE);
            }
        }
        $mylist->getItems(['getvirtuals' => 1]);

        $fieldlist = array_keys($mylist->properties);
        $this->deferred = [];
        foreach ($fieldlist as $key) {
            if (!empty($mylist->properties[$key]) && $mylist->properties[$key] instanceof DeferredItemProperty) {
                array_push($this->deferred, $key);
                // @checkme set the targetLoader to null to avoid retrieving the propname values
                if ($mylist->properties[$key] instanceof DeferredManyProperty) {
                    $mylist->properties[$key]->getDeferredLoader()->targetLoader = null;
                }
                // @checkme we need to set the item values for relational objects here
                // foreach ($mylist->items as $itemid => $item) {
                //     $mylist->properties[$key]->setItemValue($itemid, $item[$key] ?? null);
                // }
            }
        }

        return $mylist;
    }

    /**
     * Summary of getObjectItem
     * @param int $itemid
     * @return DataObject|void
     */
    public function getObjectItem(int $itemid)
    {
        $myobject = DataObjectMaster::getObject([
            'objectid' => $this->objectid,
            'itemid'   => $itemid,
            'allprops' => true,
        ]);

        if (!isset($myobject) || empty($myobject->label)) {
            return;
        }

        $myobject->getItem();

        return $myobject;
    }

    /**
     * Summary of export
     * @param mixed $objectid
     * @param mixed $itemid
     * @param mixed $format
     * @return bool|string
     */
    public static function export($objectid, $itemid = null, $format = 'xml')
    {
        $exporter = match ($format) {
            'php' => new PhpExporter($objectid),
            'json' => new JsonExporter($objectid),
            default => new XmlExporter($objectid),
        };
        if (!isset($itemid)) {
            $type = 'objectdef';
        } elseif (!is_numeric($itemid)) {
            $type = 'items';
        } else {
            $type = 'item';
        }
        return match ($type) {
            'items' => $exporter->exportItems(),
            'item' => $exporter->exportItem($itemid),
            default => $exporter->exportObjectDef(),
        };
    }
}