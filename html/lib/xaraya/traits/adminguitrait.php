<?php

/**
 * Trait to handle admin gui functions
 *
 * Usage:
 * ```
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Core\Traits\AdminGuiInterface;
 * use Xaraya\Core\Traits\AdminGuiTrait;
 *
 * class AdminGui implements AdminGuiInterface
 * {
 *     use AdminGuiTrait;
 * }
 * ```
 *
 * @package core\traits
 * @subpackage traits
 * @category Xaraya Web Applications Framework
 * @version 2.5.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Core\Traits;

use xarMod;
use sys;

sys::import('xaraya.traits.userguitrait');

/**
 * For documentation purposes only - available via AdminGuiTrait
 */
interface AdminGuiInterface extends UserGuiInterface
{
    // ...
}

/**
 * Trait to handle admin gui functions
 */
trait AdminGuiTrait
{
    use UserGuiTrait;

    protected function loadModule(): void
    {
        xarMod::load($this->moduleName, 'admin');
    }
}

/**
 * Summary of DefaultAdminGui
 */
class DefaultAdminGui implements AdminGuiInterface
{
    use AdminGuiTrait;
}
