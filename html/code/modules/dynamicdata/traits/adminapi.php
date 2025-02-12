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

namespace Xaraya\DataObject\Traits;

use Xaraya\Modules\AdminApiInterface as CoreApiInterface;
use Xaraya\Modules\AdminApiTrait as CoreApiTrait;
use Xaraya\Modules\ModuleInterface;
use sys;

sys::import('xaraya.modules.adminapitrait');
sys::import('modules.dynamicdata.traits.otherapi');

/**
 * For documentation purposes only - available via AdminApiTrait
 */
interface AdminApiInterface extends CoreApiInterface
{
    // ...
}

/**
 * Trait to handle generic admin api functions for modules with their own DD objects
 *
 * Example:
 * ```
 * use Xaraya\DataObject\Traits\AdminApiInterface;
 * use Xaraya\DataObject\Traits\AdminApiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.traits.adminapi');
 *
 * class MyClassApi implements AdminApiInterface
 * {
 *     use AdminApiTrait;
 * }
 * ```
 * @template TModule of ModuleInterface|null
 */
trait AdminApiTrait
{
    /** @use CoreApiTrait<TModule> */
    use CoreApiTrait;
    use OtherApiTrait;
}
