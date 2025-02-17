<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the modules user API
 *
 * @method mixed convertseconds(array $args = []) Update the configuration parameters of the module based on data from the modification form
 * @method mixed getitemlinks(array $args = []) Utility function to pass individual item links to whoever
 * @method mixed getitemtypes(array $args = []) Utility function to retrieve the list of itemtypes of this module (if any).
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    use OtherApiTrait;
    // ...
}
