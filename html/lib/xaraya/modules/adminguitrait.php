<?php

/**
 * Handle module admin gui functions
 *
 * Usage:
 * ```
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\AdminGuiInterface;
 * use Xaraya\Modules\AdminGuiTrait;
 *
 * class AdminGui implements AdminGuiInterface
 * {
 *     use AdminGuiTrait;
 * }
 * ```
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use xarMod;
use sys;

sys::import('xaraya.modules.userguitrait');

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
