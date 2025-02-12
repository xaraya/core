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

use Xaraya\Modules\AdminGuiInterface as CoreGuiInterface;
use Xaraya\Modules\AdminGuiTrait as CoreGuiTrait;
use Xaraya\Modules\ModuleInterface;
use sys;

sys::import('xaraya.modules.adminguitrait');
sys::import('modules.dynamicdata.traits.otherapi');

/**
 * For documentation purposes only - available via AdminGuiTrait
 */
interface AdminGuiInterface extends CoreGuiInterface
{
    // ...
}

/**
 * Trait to handle generic admin gui functions for modules with their own DD objects
 *
 * Example:
 * ```
 * use Xaraya\Modules\DynamicData\Traits\AdminGuiInterface;
 * use Xaraya\Modules\DynamicData\Traits\AdminGuiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.traits.admingui');
 *
 * class MyClassGui implements AdminGuiInterface
 * {
 *     use AdminGuiTrait;
 * }
 * ```
 * @template TModule of ModuleInterface|null
 */
trait AdminGuiTrait
{
    /** @use CoreGuiTrait<TModule> */
    use CoreGuiTrait;
    use OtherApiTrait;
}
