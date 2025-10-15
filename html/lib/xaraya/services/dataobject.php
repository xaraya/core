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
use DataObjectLoader;
use xarServer;
use xarTpl;
use sys;

sys::import('xaraya.services.servicetrait');
sys::import('modules.dynamicdata.class.objects.factory');

/**
 * For documentation purposes only - available via DataObjectTrait
 */
interface DataObjectInterface extends ServiceInterface
{
    /**
     * Get url for this object method
     * @param array<string, mixed> $args
     */
    public function getURL(string $methodName = 'view', array $args = [], ?string $objectName = null): string;

    /**
     * Render output with object template
     * @param array<mixed> $tplData
     */
    public function template(string $tplType, array $tplData = []): string;

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
     * Get data object loader
     * @param array<string> $fieldlist
     */
    public function getObjectLoader(?string $objectName = null, array $fieldlist = ['id', 'name']): DataObjectLoader|null;

    /**
     * Get info about a data object by name or objectid
     * @param array<string, mixed> $args
     * @return array<mixed>|null containing the name => value pairs for the object
     */
    public function getObjectInfo(array $args = []);

    /**
     * Summary of getObjectInterface
     * @param array<string, mixed> $args
     * @return object
     */
    public function getObjectInterface(array $args = []);

    /**
     * Get info about all data objects
     * @param array<string, mixed> $args with optional ['moduleid' => '...']
     * @return array<mixed> containing the objectid => object definition
     */
    public function getObjects(array $args = []);

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
}

/**
 * DataObjectFactory available via methods
 */
trait DataObjectTrait
{
    use ServiceTrait;

    /**
     * Get url for this object method
     * @param array<string, mixed> $args
     */
    public function getURL(string $methodName = 'view', array $args = [], ?string $objectName = null): string
    {
        $objectName ??= $this->getObjectName();
        return xarServer::getObjectURL($objectName, $methodName, $args);
    }

    /**
     * Render output with object template
     * @uses xarTpl::object()
     * @param string $tplType
     * @param array<mixed> $tplData
     * @return string
     */
    public function template(string $tplType, array $tplData = []): string
    {
        // Add standard template variables (module, itemtype and context)
        // @todo $tplData = $this->prepare($tplData);
        $tplData['context'] ??= $this->getContext();

        $modName = $this->getModName();
        $objecTemplate = $this->getObjectTemplate();

        // Create the output.
        return xarTpl::object(
            $modName,
            $objecTemplate,
            $tplType,
            $tplData
        );
    }

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
     * Get data object loader
     * @param array<string> $fieldlist
     */
    public function getObjectLoader(?string $objectName = null, array $fieldlist = ['id', 'name']): DataObjectLoader|null
    {
        $objectName ??= $this->getObjectName();
        return DataObjectFactory::getObjectLoader($objectName, $fieldlist, $this->getContext());
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
     * Get data object user interface
     * @param array<string, mixed> $args
     * @return object
     */
    public function getObjectInterface(array $args = [])
    {
        return DataObjectFactory::getObjectInterface($args);
    }

    /**
     * Get info about all data objects
     * @param array<string, mixed> $args with optional ['moduleid' => '...']
     * @return array<mixed> containing the objectid => object definition
     */
    public function getObjects(array $args = [])
    {
        return DataObjectFactory::getObjects($args);
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
}

/**
 * Access DataObject*::* methods with context (getObject, getObjectList, ...)
 *
 * Available methods:
 * - getURL() for current object - or use ctl()->getObjectURL() in general with objectName
 * - template() for current object - or use tpl()->object() in general with modName objectTemplate
 * - getObject()
 * - getObjectList()
 * - getObjectLoader()
 * - getObjectInfo()
 * - getObjects()
 * - getObjectID()
 * - getObjectDescriptor()
 * - ...
 *
 * Required methods in parent:
 * - getObjectName() for data()->getURL()
 * - getModName() for data()->template()
 * - getObjectTemplate() for data()->template()
 *
 * @todo do something with getParent()->getObject() + simplify methods by name or objectid?
 *
 */
class DataObjectService implements DataObjectInterface
{
    use DataObjectTrait;

    /**
     * Get name of the object from parent getObject()
     */
    public function getObjectName(): string
    {
        return $this->getParent()->getObject()?->name;
    }

    /**
     * Get name of the module from parent getObject()
     */
    public function getModName(): string
    {
        return $this->getParent()->getObject()?->tplmodule;
    }

    /**
     * Get template of the object from parent getObject()
     */
    public function getObjectTemplate(): string
    {
        return $this->getParent()->getObject()?->template;
    }
}
