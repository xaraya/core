<?php

/**
 * Core services for data object or objectlist classes
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataProperty;

use Xaraya\Services\ParentServicesInterface;
use Xaraya\Services\ParentServicesTrait;
use Xaraya\DataObject\DataObjectServicesInterface;
use VirtualObjectDescriptor;
use DataObject;
//use DataObjectList;
use DataProperty;
use sys;

sys::import('xaraya.services.parentservicestrait');
sys::import('modules.dynamicdata.class.objects.servicestrait');
sys::import('modules.dynamicdata.class.objects.virtual');

/**
 * For documentation purposes only - available via DataPropertyServicesTrait
 */
interface DataPropertyServicesInterface extends ParentServicesInterface
{
    // ...
}

/**
 * Child class using services from parent class
 * e.g. method -> module or property -> object
 */
trait DataPropertyServicesTrait
{
    use ParentServicesTrait;

    /** @var ?DataObject */
    protected static $dummyObject = null;

    protected DataObjectServicesInterface $parent;

    /**
     * Get parent class for access to core services = data object here
     */
    public function getParent(): DataObjectServicesInterface
    {
        return $this->objectref ?? $this->getDummyObject();
    }

    /**
     * Get dummy virtual object as parent for stand-alone property
     * @return DataObject
     */
    protected function getDummyObject()
    {
        if (!isset(static::$dummyObject)) {
            // needed for installation after phase 5
            sys::import('modules.dynamicdata.class.objects.base');
            $descriptor = new VirtualObjectDescriptor(['name' => 'dummy']);
            static::$dummyObject = new DataObject($descriptor);
        }
        $object = clone static::$dummyObject;
        $object->setContext($this->getContext());
        return $object;
    }

    /**
     * Get data object or objectlist from here
     * @todo check out if we actually need this
     */
    //public function getObject(): DataObjectList|DataObject|null
    //{
    //    return $this->objectref;
    //}

    /**
     * Get data property from here
     * @todo check out if we actually need this
     */
    public function getProperty(): DataProperty
    {
        return $this;
    }
}
