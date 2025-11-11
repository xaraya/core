<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\Modules\DynamicData\Traits;

use Xaraya\Modules\UserApiInterface as CoreApiInterface;
use Xaraya\Modules\UserApiTrait as CoreApiTrait;
use Xaraya\Modules\ModuleInterface;
use sys;

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
 * use Xaraya\Modules\DynamicData\Traits\UserApiInterface;
 * use Xaraya\Modules\DynamicData\Traits\UserApiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.traits.userapi');
 *
 * class MyClassApi implements UserApiInterface
 * {
 *     use UserApiTrait;
 * }
 * ```
 * @template TModule of ModuleInterface|null
 */
trait UserApiTrait
{
    /** @use CoreApiTrait<TModule> */
    use CoreApiTrait;
    use ItemLinksTrait;
    use OtherApiTrait;

    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public function getModuleObjects(): array
    {
        return $this->getItemLinkObjects();
    }
}

/**
 * Summary of UserApiClass
 * @template TModule of ModuleInterface|null
 */
class UserApiClass implements UserApiInterface
{
    /** @use UserApiTrait<TModule> */
    use UserApiTrait;
}
