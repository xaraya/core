<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

use Xaraya\Modules\UserApiInterface as CoreApiInterface;
use Xaraya\Modules\UserApiTrait as CoreApiTrait;
use sys;

sys::import('xaraya.modules.userapitrait');
sys::import('modules.dynamicdata.class.traits.itemlinks');

/**
 * For documentation purposes only - available via UserApiTrait
 */
interface UserApiInterface extends CoreApiInterface, ItemLinksInterface
{
    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public function getModuleObjects(): array;
}

/**
 * Trait to handle generic user api functions for modules with their own DD objects
 *
 * Example:
 * ```
 * use Xaraya\DataObject\Traits\UserApiInterface;
 * use Xaraya\DataObject\Traits\UserApiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.class.traits.userapi');
 *
 * class MyClassApi implements UserApiInterface
 * {
 *     use UserApiTrait;
 *     protected static int $moduleId = 18252;
 *     protected static int $itemtype = 0;
 * }
 * ```
 * @todo replace with instance methods
 */
trait UserApiTrait
{
    use CoreApiTrait;
    use ItemLinksTrait;

    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public function getModuleObjects(): array
    {
        return $this->getItemLinkObjects();
    }
}
