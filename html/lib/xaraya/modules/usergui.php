<?php

/**
 * Handle module user gui functions
 *
 * Usage:
 * ```
 * # class/usergui.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\UserGuiClass;
 *
 * /**
 *  * Handle module user gui functions
 *  * @extends UserGuiClass<Module>
 *  *\/
 * class UserGui extends UserGuiClass
 * {
 *     public function main($args = []) {
 *         // get main user overview
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

use sys;

sys::import('xaraya.modules.userguitrait');

/**
 * Handle module user gui functions
 * @template TModule of ModuleInterface|null
 */
class UserGuiClass implements UserGuiInterface
{
    /** @use UserGuiTrait<TModule> */
    use UserGuiTrait;
}
