<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\Modules\DynamicData\Traits;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
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
interface ItemLinksInterface extends ContextInterface
{
    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public function getItemLinkObjects(): array;

    /**
     * Utility function to retrieve the list of itemtypes of this module (if any).
     * @param array<string, mixed> $args array of optional parameters
     * @return array<mixed> the itemtypes of this module and their description
     */
    public function getItemTypes(array $args = []): array;

    /**
     * Utility function to pass individual item links to whoever
     * @param array<string, mixed> $args array of optional parameters
     *        string   $args['itemtype'] item type (optional)
     *        array    $args['itemids'] array of item ids to get
     * @return array<mixed> containing the itemlink(s) for the item(s).
     */
    public function getItemLinks(array $args = []): array;
}

/**
 * Trait to handle getitemtypes and getitemlinks user api
 * for modules with their own DD objects
 */
trait ItemLinksTrait
{
    use ContextTrait;

    /** @var array<string, mixed> */
    protected static array $_itemlinkObjects = [];

    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @todo check use of static::$_itemlinkObjects
     * @return array<string, array<string, mixed>>
     */
    public function getItemLinkObjects(): array
    {
        if (!empty(static::$_itemlinkObjects[$this->getModName()])) {
            return static::$_itemlinkObjects[$this->getModName()];
        }
        $moduleId = $this->mod()->getRegID();
        $objects = DataObjectFactory::getObjects();
        static::$_itemlinkObjects[$this->getModName()] = [];
        foreach ($objects as $objectid => $objectinfo) {
            /** @var array<string, mixed> $objectinfo */
            if (intval($objectinfo['moduleid']) !== $moduleId) {
                continue;
            }
            if (method_exists(static::class, 'getItemType')) {
                if (intval($objectinfo['itemtype']) > $this->getItemType()) {
                    $this->setItemType(intval($objectinfo['itemtype']));
                }
            }
            static::$_itemlinkObjects[$this->getModName()][$objectinfo['name']] = $objectinfo;
        }
        return static::$_itemlinkObjects[$this->getModName()];
    }

    /**
     * Utility function to retrieve the list of itemtypes of this module (if any).
     * @param array<string, mixed> $args array of optional parameters
     *        string   $args['linktype'] link type (optional)
     *        string   $args['linkfunc'] link func (optional)
     *        string   $args['tplmodule'] tpl module (optional)
     * @return array<mixed> the itemtypes of this module and their description
     */
    public function getItemTypes(array $args = []): array
    {
        extract($args);

        if (empty($linktype)) {
            $linktype = 'object';
        }
        if (empty($linkfunc)) {
            $linkfunc = 'view';
        }
        if (empty($tplmodule)) {
            $tplmodule = 'dynamicdata';
        }

        $objects = $this->getItemLinkObjects();
        $itemtypes = [];
        foreach ($objects as $name => $objectinfo) {
            // skip the "internal" DD objects
            if ($objectinfo['objectid'] < 4) {
                continue;
            }
            if ($linktype == 'object') {
                $url = $this->ctl()->getObjectURL($objectinfo['name'], $linkfunc);
            } else {
                // adapted from xarMod::apiFunc('dynamicdata', 'user', 'getitemtypes')
                $url = $this->ctl()->getModuleURL($tplmodule, $linktype, $linkfunc, ['itemtype' => $objectinfo['itemtype']]);
            }
            $itemtypes[$objectinfo['itemtype']] = [
                'objectid' => $objectinfo['objectid'],
                'name'     => $objectinfo['name'],
                'label'    => $this->var()->prep($objectinfo['label']),
                'title'    => $this->var()->prep($this->mls()->translate('View #(1)', $objectinfo['label'])),
                'url'      => $url,
            ];
        }
        return $itemtypes;
    }

    /**
     * Utility function to pass individual item links to whoever
     * @param array<string, mixed> $args array of optional parameters
     *        string   $args['itemtype'] item type (optional)
     *        array    $args['itemids'] array of item ids to get
     *        string   $args['linktype'] link type (optional)
     *        string   $args['linkfunc'] link func (optional)
     *        string   $args['tplmodule'] tpl module (optional)
     * @return array<mixed> containing the itemlink(s) for the item(s).
     */
    public function getItemLinks(array $args = []): array
    {
        extract($args);

        $itemlinks = [];
        if (empty($itemtype)) {
            $itemtype = null;
        }
        if (empty($itemids)) {
            $itemids = null;
        }
        if (empty($linktype)) {
            $linktype = 'object';
        }
        if (empty($linkfunc)) {
            $linkfunc = 'display';
        }
        if (empty($tplmodule)) {
            $tplmodule = 'dynamicdata';
        }

        // for items managed by this module itself only
        $moduleId = $this->mod()->getRegID();
        $args = $this->data()->getObjectID([
            'moduleid'  => $moduleId,
            'itemtype'  => $itemtype,
        ]);
        if (empty($args['objectid'])) {
            return $itemlinks;
        }
        $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
        // set context if available in method
        $object = $this->data()->getObjectList(
            ['objectid'  => $args['objectid'],
                'itemids' => $itemids,
                'status' => $status],
        );
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
                $label = $this->mls()->translate('Item #(1)', $itemid);
            }
            // $object->getActionURL('display', $itemid)
            if ($linktype == 'object') {
                $url = $this->ctl()->getObjectURL($object->name, $linkfunc, ['itemid' => $itemid]);
            } else {
                // adapted from xarMod::apiFunc('dynamicdata', 'user', 'getitemlinks')
                $url = $this->ctl()->getModuleURL($tplmodule, $linktype, $linkfunc, ['name' => $args['name'], 'itemid' => $itemid]);
            }
            $itemlinks[$itemid] = [
                'objectid' => $object->objectid,
                'name'     => $object->name,
                'itemid'   => $itemid,
                'url'      => $url,
                'title'    => $this->mls()->translate('Display Item'),
                'label'    => $label,
            ];
        }
        return $itemlinks;
    }
}
