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
//use DataObject;
//use DataObjectList;
use DataProperty;
use sys;

sys::import('xaraya.services.parentservicestrait');
sys::import('modules.dynamicdata.class.objects.servicestrait');

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
 *
 * @todo don't use TParent template here
 */
trait DataPropertyServicesTrait
{
    /** @use ParentServicesTrait<DataObjectServicesInterface> */
    use ParentServicesTrait;

    protected DataObjectServicesInterface $parent;

    /**
     * @todo check out if we actually need this
     */
    public function getParent(): DataObjectServicesInterface
    {
        return $this->objectref;
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
