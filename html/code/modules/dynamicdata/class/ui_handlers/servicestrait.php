<?php
/**
 * Core services for ui handler classes
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

namespace Xaraya\DataObject\Handlers;

use Xaraya\Services\ServicesInterface;
use Xaraya\Services\ServicesTrait;
use DataObjectList;
use DataObject;
use sys;

sys::import('xaraya.services.servicestrait');
sys::import('xaraya.objects');

interface HandlerServicesInterface extends ServicesInterface
{
    // ...
}

/**
 * Services trait for ui handler classes
 *
 * This defines where to get modName, itemType, modType and object
 * from the parent ui handler class for use by the service classes
 */
trait HandlerServicesTrait
{
    use ServicesTrait;

    /**
     * Get name for the module from ui handler class
     */
    public function getModName(): string
    {
        return $this->tplmodule ?? $this->getObject()?->tplmodule ?? 'dynamicdata';
    }

    /**
     * Get item type from from ui handler class
     */
    public function getItemType(): int
    {
        return $this->getObject()?->itemtype ?? 0;
    }

    /**
     * Get module type (user, admin, ...) from ui handler class
     */
    public function getModType(): string
    {
        // @todo check this out for other modules
        return 'object';
    }

    /**
     * Get data object or objectlist from ui handler class
     */
    public function getObject(): DataObjectList|DataObject|null
    {
        return $this->object;
    }
}
