<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

use DataObjectDescriptor;
use DataObjectFactory;
use DataPropertyMaster;
use xarServer;
use xarVar;
use sys;

sys::import('modules.dynamicdata.class.objects.factory');

/**
 * For documentation purposes only - available via ItemLinksTrait
 */
interface ItemLinksInterface
{
    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public static function getItemLinkObjects(): array;

    /**
     * Utility function to retrieve the list of itemtypes of this module (if any).
     * @param array<string, mixed> $args array of optional parameters
     * @param mixed $context
     * @return array<mixed> the itemtypes of this module and their description
     */
    public static function getItemTypes(array $args = [], $context = null): array;

    /**
     * Utility function to pass individual item links to whoever
     * @param array<string, mixed> $args array of optional parameters
     *        string   $args['itemtype'] item type (optional)
     *        array    $args['itemids'] array of item ids to get
     * @param mixed $context
     * @return array<mixed> containing the itemlink(s) for the item(s).
     */
    public static function getItemLinks(array $args = [], $context = null): array;
}

/**
 * Trait to handle getitemtypes and getitemlinks user api
 * for modules with their own DD objects
 */
trait ItemLinksTrait
{
    //protected static int $moduleId = 123456;
    //protected static int $itemtype = 0;
    /** @var array<string, mixed> */
    protected static array $_itemlinkObjects = [];

    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, array<string, mixed>>
     */
    public static function getItemLinkObjects(): array
    {
        if (!empty(static::$_itemlinkObjects)) {
            return static::$_itemlinkObjects;
        }
        $objects = DataObjectFactory::getObjects();
        static::$_itemlinkObjects = [];
        foreach ($objects as $objectid => $objectinfo) {
            /** @var array<string, mixed> $objectinfo */
            if (intval($objectinfo['moduleid']) !== static::$moduleId) {
                continue;
            }
            if (property_exists(static::class, 'itemtype')) {
                if (intval($objectinfo['itemtype']) > static::$itemtype) {
                    static::$itemtype = intval($objectinfo['itemtype']);
                }
            }
            static::$_itemlinkObjects[$objectinfo['name']] = $objectinfo;
        }
        return static::$_itemlinkObjects;
    }

    /**
     * Utility function to retrieve the list of itemtypes of this module (if any).
     * @param array<string, mixed> $args array of optional parameters
     * @param mixed $context
     * @return array<mixed> the itemtypes of this module and their description
     */
    public static function getItemTypes(array $args = [], $context = null): array
    {
        $objects = static::getItemLinkObjects();
        $itemtypes = [];
        foreach ($objects as $name => $objectinfo) {
            $itemtypes[$objectinfo['itemtype']] = [
                'label' => xarVar::prepForDisplay($objectinfo['label']),
                'title' => xarVar::prepForDisplay(xarML('View #(1)', $objectinfo['label'])),
                'url'   => xarServer::getObjectURL($objectinfo['name'], 'view'),
            ];
        }
        return $itemtypes;
    }

    /**
     * Utility function to pass individual item links to whoever
     * @param array<string, mixed> $args array of optional parameters
     *        string   $args['itemtype'] item type (optional)
     *        array    $args['itemids'] array of item ids to get
     * @param mixed $context
     * @return array<mixed> containing the itemlink(s) for the item(s).
     */
    public static function getItemLinks(array $args = [], $context = null): array
    {
        extract($args);

        $itemlinks = [];
        if (empty($itemtype)) {
            $itemtype = null;
        }
        if (empty($itemids)) {
            $itemids = null;
        }

        // for items managed by library itself only
        $args = DataObjectDescriptor::getObjectID(['moduleid'  => static::$moduleId,
                                        'itemtype'  => $itemtype]);
        if (empty($args['objectid'])) {
            return $itemlinks;
        }
        $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
        // set context if available in method
        $object = DataObjectFactory::getObjectList(['objectid'  => $args['objectid'],
                                            'itemids' => $itemids,
                                            'status' => $status],
                                            $context);
        if (!isset($object) || (empty($object->objectid) && empty($object->table))) {
            return $itemlinks;
        }
        if (!$object->checkAccess('view')) {
            return $itemlinks;
        }

        $object->getItems();

        $properties = & $object->getProperties();
        $items = & $object->items;
        if (!isset($items) || !is_array($items) || count($items) == 0) {
            return $itemlinks;
        }

        // TODO: make configurable
        $titlefield = '';
        foreach ($properties as $name => $property) {
            // let's use the first textbox property we find for now...
            if ($property->type == 2) {
                $titlefield = $name;
                break;
            }
        }

        // if we didn't have a list of itemids, return all the items we found
        if (empty($itemids)) {
            $itemids = array_keys($items);
        }

        foreach ($itemids as $itemid) {
            if (!empty($titlefield) && isset($items[$itemid][$titlefield])) {
                $label = $items[$itemid][$titlefield];
            } else {
                $label = xarML('Item #(1)', $itemid);
            }
            // $object->getActionURL('display', $itemid)
            $itemlinks[$itemid] = ['url'   => xarServer::getObjectURL($object->name, 'display', ['itemid' => $itemid]),
                                   'title' => xarML('Display Item'),
                                   'label' => $label];
        }
        return $itemlinks;
    }
}
