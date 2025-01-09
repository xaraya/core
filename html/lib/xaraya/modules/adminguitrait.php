<?php

/**
 * Handle module admin gui functions
 *
 * Usage:
 * ```
 * # class/admingui.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\AdminGuiInterface;
 * use Xaraya\Modules\AdminGuiTrait;
 *
 * class AdminGui implements AdminGuiInterface
 * {
 *     use AdminGuiTrait;
 *
 *     public function main($args = []) {
 *         // get main admin overview
 *         // $context = $this->getContext();
 *         return $output;
 *     }
 * }
 * ```
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
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
 * Module class supports admin gui methods - available via AdminGuiTrait
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

    public function configure()
    {
        $this->moduleType = 'admin';
        xarMod::load($this->moduleName, $this->moduleType);
    }
}
