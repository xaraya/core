<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

use sys;

sys::import('modules.dynamicdata.class.traits.itemlinks');

/**
 * For documentation purposes only - available via UserApiTrait
 */
interface UserApiInterface extends ItemLinksInterface
{
    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public static function getModuleObjects(): array;
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
 */
trait UserApiTrait
{
    use ItemLinksTrait;

    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public static function getModuleObjects(): array
    {
        return static::getItemLinkObjects();
    }
}