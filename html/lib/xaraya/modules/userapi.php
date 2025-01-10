<?php

/**
 * Handle module user api functions
 *
 * Usage:
 * ```
 * # class/userapi.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\UserApiClass;
 *
 * /**
 *  * Handle module user api functions
 *  * @extends UserApiClass<Module>
 *  *\/
 * class UserApi extends UserApiClass
 * {
 *     public function get($args = []) {
 *         // get single module item
 *         // $context = $this->getContext();
 *         return $data;
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

sys::import('xaraya.modules.userapitrait');

/**
 * Handle module user api functions
 * @template TModule of ModuleInterface|null
 */
class UserApiClass implements UserApiInterface
{
    /** @use UserApiTrait<TModule> */
    use UserApiTrait;
}
