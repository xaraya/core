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

use Xaraya\DataObject\Export\DataObjectExporter;
use Xaraya\DataObject\Import\DataObjectImporter;
use DataObjectDescriptor;
use DataObjectFactory;
use DataObject;
use DataObjectList;
use DataObjectLoader;
use DataObjectUserInterface;
use EmptyParameterException;

/**
 * For documentation purposes only - available via DataObjectTrait
 */
interface DataObjectInterface extends ServiceInterface
{
    public const SLICE = 'dataobject';

    /**
     * Get url for this object method
     * @param array<string, mixed> $args
     * @deprecated 2.9.0 use xar::ctl()->getObjectURL() instead
     */
    public function getURL(string $methodName = 'view', array $args = [], ?string $objectName = null): string;

    /**
     * Render output with object template
     * @param array<mixed> $tplData
     * @deprecated 2.9.2 use xar::tpl()->object() instead
     */
    public function template(string $tplType, array $tplData = []): string;

    /**
     * Call a dataobject user interface method (maybe from index.php someday)
     * @param array<string, mixed> $args arguments to pass to the method
     */
    public function guiMethod(string $objectName, string $methodName = 'view', array $args = []): string;

    /**
     * Get data object
     * @param array<string, mixed> $args
     */
    public function getObject(array $args = []): ?DataObject;

    /**
     * Get data object list
     * @param array<string, mixed> $args
     */
    public function getObjectList(array $args = []): ?DataObjectList;

    /**
     * Get data object loader
     * @param array<string> $fieldlist
     */
    public function getObjectLoader(string $objectName, array $fieldlist = ['id', 'name']): ?DataObjectLoader;

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
     * Summary of createObject
     * @param array<string, mixed> $args
     * @return int
     */
    public function createObject(array $args = []);

    /**
     * Summary of updateObject
     * @param array<string, mixed> $args
     * @return int|mixed
     */
    public function updateObject(array $args = []);

    /**
     * Summary of deleteObject
     * @param array<string, mixed> $args
     * @return bool
     */
    public function deleteObject(array $args = []);

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
     * @deprecated 2.9.0 use xar::ctl()->getObjectURL() instead
     */
    public function getURL(string $methodName = 'view', array $args = [], ?string $objectName = null): string
    {
        $objectName ??= $this->getObjectName();
        $ctl = $this->getServicesClass()->ctl();
        return $ctl->getObjectURL($objectName, $methodName, $args);
    }

    /**
     * Render output with object template
     * @uses xar::tpl()->object()
     * @param string $tplType
     * @param array<mixed> $tplData
     * @return string
     * @deprecated 2.9.2 use xar::tpl()->object() instead
     */
    public function template(string $tplType, array $tplData = []): string
    {
        // Add standard template variables (context)
        $tplData['context'] ??= $this->getContext();

        $modName = $this->getModName();
        $objecTemplate = $this->getObjectTemplate();

        $tpl = $this->getServicesClass()->tpl();

        // Create the output.
        return $tpl->object(
            $modName,
            $objecTemplate,
            $tplType,
            $tplData,
        );
    }

    /**
     * Call a dataobject user interface method (maybe from index.php someday)
     * @param array<string, mixed> $args arguments to pass to the method
     */
    public function guiMethod(string $objectName, string $methodName = 'view', array $args = []): string
    {
        if (empty($objectName)) {
            throw new EmptyParameterException('objectName');
        }
        $xar = $this->getServicesClass();

        // Pass the object name and method to the userinterface class
        $args['object'] = $objectName;
        $args['method'] = $methodName;
        $context = $this->getContext();
        // Set module name and type in context if needed (dummy)
        $context['module'] ??= 'object';
        $context['modtype'] ??= $objectName;

        // @todo refine configuration elsewhere later
        $twig_support = $xar->mod('dynamicdata')->getVar('twig_support');
        if (!empty($twig_support)) {
            if (empty($context['twig'])) {
                $context['twig'] = true;
            }
        }

        $interface = new DataObjectUserInterface($args, $context, $xar);
        return $interface->handle($args);
    }

    /**
     * Get data object
     * @param array<string, mixed> $args
     */
    public function getObject(array $args = []): ?DataObject
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::getObject($args, $this->getContext(), $xar);
    }

    /**
     * Get data object list
     * @param array<string, mixed> $args
     */
    public function getObjectList(array $args = []): ?DataObjectList
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::getObjectList($args, $this->getContext(), $xar);
    }

    /**
     * Get data object loader
     * @param array<string> $fieldlist
     */
    public function getObjectLoader(string $objectName, array $fieldlist = ['id', 'name']): ?DataObjectLoader
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::getObjectLoader($objectName, $fieldlist, $this->getContext(), $xar);
    }

    /**
     * Get info about a data object by name or objectid
     * @param array<string, mixed> $args
     * @return array<mixed>|null containing the name => value pairs for the object
     */
    public function getObjectInfo(array $args = [])
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::getObjectInfo($args, $xar);
    }

    /**
     * Get data object user interface
     * @param array<string, mixed> $args
     * @return object
     */
    public function getObjectInterface(array $args = [])
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::getObjectInterface($args, $this->getContext(), $xar);
    }

    /**
     * Get info about all data objects
     * @param array<string, mixed> $args with optional ['moduleid' => '...']
     * @return array<mixed> containing the objectid => object definition
     */
    public function getObjects(array $args = [])
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::getObjects($args, $xar);
    }

    /**
     * Summary of createObject
     * @param array<string, mixed> $args
     * @return int
     */
    public function createObject(array $args = [])
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::createObject($args, $xar);
    }

    /**
     * Summary of updateObject
     * @param array<string, mixed> $args
     * @return int|mixed
     */
    public function updateObject(array $args = [])
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::updateObject($args, $xar);
    }

    /**
     * Summary of deleteObject
     * @param array<string, mixed> $args
     * @return bool
     */
    public function deleteObject(array $args = [])
    {
        $xar = $this->getServicesClass();
        return DataObjectFactory::deleteObject($args, $xar);
    }

    /**
     * Identify data object via DataObjectDescriptor
     * @param array<string, mixed> $args
     * @return array<mixed> all parts necessary to describe a DataObject
     */
    public function getObjectID(array $args = [])
    {
        $xar = $this->getServicesClass();
        return DataObjectDescriptor::getObjectID($args, $xar);
    }

    /**
     * Get data object descriptor
     * @param array<string, mixed> $args
     */
    public function getObjectDescriptor(array $args = []): DataObjectDescriptor
    {
        $xar = $this->getServicesClass();
        return new DataObjectDescriptor($args, $xar);
    }

    public function export($objectid, $itemid = null, $format = 'xml', $tofile = false)
    {
        $xar = $this->getServicesClass();
        return DataObjectExporter::export($objectid, $itemid, $format, $tofile, $xar);
    }

    public function import($file = null, $content = null, $format = 'xml', $prefix = null, $overwrite = false, $keepitemid = false)
    {
        $xar = $this->getServicesClass();
        return DataObjectImporter::import($file, $content, $format, $prefix, $overwrite, $keepitemid, $xar);
    }
}

/**
 * Access DataObject*::* methods with context (getObject, getObjectList, ...)
 *
 * Available methods:
 * - getURL() for current object - @deprecated 2.9.0 use xar::ctl()->getObjectURL() instead
 * - template() for current object - @deprecated 2.9.2 use xar::tpl()->object() instead
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
 * - getObjectName() for data()->getURL() - @deprecated 2.9.0 use xar::ctl()->getObjectURL() instead
 * - getModName() for data()->template() - @deprecated 2.9.2 use xar::tpl()->object() instead
 * - getObjectTemplate() for data()->template() - @deprecated 2.9.2 use xar::tpl()->object() instead
 *
 * @todo do something with getParent()->getObject() + simplify methods by name or objectid?
 *
 */
class DataObjectService implements DataObjectInterface
{
    use DataObjectTrait;

    /**
     * Get name of the object from parent getObject()
     * @deprecated 2.9.0 use xar::ctl()->getObjectURL() instead
     */
    public function getObjectName(): string
    {
        return $this->getParent()->getObject()?->name;
    }

    /**
     * Get name of the module from parent getObject()
     * @deprecated 2.9.2 use xar::tpl()->object() instead
     */
    public function getModName(): string
    {
        return $this->getParent()->getObject()?->tplmodule;
    }

    /**
     * Get template of the object from parent getObject()
     * @deprecated 2.9.2 use xar::tpl()->object() instead
     */
    public function getObjectTemplate(): string
    {
        return $this->getParent()->getObject()?->template;
    }
}
