<?php

/**
 * Core services for block classes
 * @package core\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Blocks;

use Xaraya\Services\ServicesInterface;
use Xaraya\Services\ServicesTrait;
use sys;

sys::import('xaraya.services.servicestrait');

interface BlockServicesInterface extends ServicesInterface
{
    // ...
}

/**
 * Services trait for block classes
 *
 * This defines where to get modName, itemType, modType and object
 * from the actual block class for use by the service classes
 */
trait BlockServicesTrait
{
    use ServicesTrait;

    /**
     * Get name of the module from block class
     */
    public function getModName(): string
    {
        return $this->module;
    }

    /**
     * Get item type from block class - N/A
     */
    public function getItemType(): int
    {
        return 0;
    }

    /**
     * Get module type (user, admin, ...) from block class - N/A?
     */
    public function getModType(): string
    {
        // @todo check this out for other modules
        return 'blocks';
    }

    /**
     * Get block type from block class
     */
    public function getBlockType(): string
    {
        return $this->type;
    }

    /**
     * Get data object or objectlist from block class - N/A
     */
    public function getObject(): null
    {
        return null;
    }

    /**
     * Get data property from block class - N/A
     */
    public function getProperty(): null
    {
        return null;
    }
}
