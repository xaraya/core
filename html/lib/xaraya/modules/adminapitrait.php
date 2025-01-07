<?php

/**
 * Handle module admin api functions
 *
 * Usage:
 * ```
 * # class/adminapi.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\AdminApiInterface;
 * use Xaraya\Modules\AdminApiTrait;
 *
 * class AdminApi implements AdminApiInterface
 * {
 *     use AdminApiTrait;
 *
 *     public function create($args = []) {
 *         // create module item
 *         return $args;
 *     }
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

sys::import('xaraya.modules.userapitrait');

/**
 * For documentation purposes only - available via AdminApiTrait
 */
interface AdminApiInterface extends UserApiInterface
{
    // ...
}

/**
 * Trait to handle admin api functions
 */
trait AdminApiTrait
{
    use UserApiTrait;

    protected function loadModule(): void
    {
        xarMod::apiLoad($this->moduleName, 'admin');
    }
}
