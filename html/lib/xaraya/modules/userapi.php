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
 *
 * User api methods:
 * - ... all other module user api functions implemented here or as method classes
 *
 * Available methods:
 * - configure() Provide additional api/gui configuration when created
 * - getModType() Get module type of this module class (user, admin, ...)
 * - getModule() Access other api/gui module classes from this instance
 * - hasMethod($funcName, $callType = 'api') Does this module class implement this method
 *
 * Inherited methods:
 * - Module:
 *   - getModName() Get name for this module in module class or method
 *   - getModId() Get module registry ID for this module
 *   - getItemType() Get item type in this module class
 *   - setItemType($itemtype = 0) Set item type in this module class
 *   - getModVar($varName) Get module variable for this module
 *   - setModVar($varName, $value) Set module variable for this module
 * - Security:
 *   - checkAccess($mask, $action = '', $instance = null) Check access based on security mask or module action
 *   - genAuthKey() Generate authorisation key for this module
 *   - confirmAuthKey($name = 'authid') Confirm authorisation key for this module
 * - Variable:
 *   - fetch($name, $validation, &$value, $defaultValue = null, $flags, $prep) Fetch variable by name, with validation, default, flags and prep
 * - Controller:
 *   - getUrl($modType = 'user', $funcName = 'main', $args = []) Get url for this module type function
 *   - redirect($url, $httpResponse = null) Send redirect to url and exit
 * - Multi-language:
 *   - translate($rawstring, ...$args) Translate string with optional arguments
 * - System:
 *   - exit($status = 0) Call exit() - override for non-blocking servers, php unit tests or elsewhere
 *
 * @template TModule of ModuleInterface|null
 */
class UserApiClass implements UserApiInterface
{
    /** @use UserApiTrait<TModule> */
    use UserApiTrait;
}
