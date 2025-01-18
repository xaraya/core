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

namespace Xaraya\DataObject;

use Xaraya\Services\ServicesInterface;
use Xaraya\Services\ServicesTrait;
use DataObject;
use DataObjectList;
use sys;

sys::import('xaraya.services.servicestrait');

interface DataObjectServicesInterface extends ServicesInterface
{
    // ...
}

/**
 * Services trait for data object or objectlist classes
 *
 * This defines where to get modName, itemType, modType and object
 * from the parent data object class for use by the service classes
 *
 * @template TParent of ServicesInterface
 */
trait DataObjectServicesTrait
{
    /** @use ServicesTrait<TParent> */
    use ServicesTrait;

    /**
     * Get name for the module from data object class
     */
    public function getModName(): string
    {
        return $this->tplmodule ?? 'dynamicdata';
    }

    /**
     * Get item type from from data object class
     */
    public function getItemType(): int
    {
        return $this->itemtype ?? 0;
    }

    /**
     * Get module type (user, admin, ...) from data object class
     */
    public function getModType(): string
    {
        // @todo check this out for other modules
        return 'object';
    }

    /**
     * Get data object or objectlist from data object class
     */
    public function getObject(): DataObject|DataObjectList
    {
        return $this;
    }
}
