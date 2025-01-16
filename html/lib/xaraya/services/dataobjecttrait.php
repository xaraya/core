<?php

/**
 * DataObjectFactory available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use DataObjectDescriptor;
use DataObjectFactory;
use DataObject;
use DataObjectList;
use DataPropertyMaster;
use sys;

sys::import('xaraya.services.servicetrait');
sys::import('modules.dynamicdata.class.objects.factory');

/**
 * For documentation purposes only - available via DataObjectTrait
 */
interface DataObjectInterface extends ServiceInterface
{
    /**
     * Get data object
     * @param array<string, mixed> $args
     */
    public function getObject(array $args = []): DataObject|null;

    /**
     * Get data object list
     * @param array<string, mixed> $args
     */
    public function getObjectList(array $args = []): DataObjectList|null;

    /**
     * Get info about a data object by name or objectid
     * @param array<string, mixed> $args
     * @return array<mixed>|null containing the name => value pairs for the object
     */
    public function getObjectInfo(array $args = []);

    /**
     * Identify data object via DataObjectDescriptor
     * @param array<string, mixed> $args
     * @return array<mixed> all parts necessary to describe a DataObject
     */
    public function getObjectID(array $args = []);

    /**
     * Get data object descriptor
     * @param array<string, mixed> $args
     */
    public function getObjectDescriptor(array $args = []): DataObjectDescriptor;

    /**
     * List all defined property types
     * @return array<int, mixed>
     */
    public function getPropertyTypes(): array;
}

/**
 * DataObjectFactory available via methods
 * @template TParent of ServicesInterface
 */
trait DataObjectTrait
{
    /** @use ServiceTrait<TParent> */
    use ServiceTrait;

    /**
     * Get data object
     * @param array<string, mixed> $args
     */
    public function getObject(array $args = []): DataObject|null
    {
        return DataObjectFactory::getObject($args, $this->getContext());
    }

    /**
     * Get data object list
     * @param array<string, mixed> $args
     */
    public function getObjectList(array $args = []): DataObjectList|null
    {
        return DataObjectFactory::getObjectList($args, $this->getContext());
    }

    /**
     * Get info about a data object by name or objectid
     * @param array<string, mixed> $args
     * @return array<mixed>|null containing the name => value pairs for the object
     */
    public function getObjectInfo(array $args = [])
    {
        return DataObjectFactory::getObjectInfo($args);
    }

    /**
     * Identify data object via DataObjectDescriptor
     * @param array<string, mixed> $args
     * @return array<mixed> all parts necessary to describe a DataObject
     */
    public function getObjectID(array $args = [])
    {
        return DataObjectDescriptor::getObjectID($args);
    }

    /**
     * Get data object descriptor
     * @param array<string, mixed> $args
     */
    public function getObjectDescriptor(array $args = []): DataObjectDescriptor
    {
        return new DataObjectDescriptor($args);
    }

    /**
     * List all defined property types
     * @return array<int, mixed>
     */
    public function getPropertyTypes(): array
    {
        return DataPropertyMaster::getPropertyTypes();
    }
}

/**
 * Access DataObject*::* methods with context (getObject, getObjectList, ...)
 *
 * Available methods:
 * - getObject()
 * - getObjectList()
 * - getObjectInfo()
 * - getObjectID()
 * - getObjectDescriptor()
 * - getPropertyTypes()
 * - ...
 *
 * @todo do something with getParent()->getObject() + simplify methods by name or objectid?
 *
 * @template TParent of ServicesInterface
 */
class DataObjectService implements DataObjectInterface
{
    /** @use DataObjectTrait<TParent> */
    use DataObjectTrait;
}
