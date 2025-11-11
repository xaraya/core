<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem;

use Xaraya\Modules\UserApiClass;

/**
 * Handle the authsystem user API
 *
 * @method mixed authenticateUser(array $args = []) Authenticate a user
 * @method mixed login(array $args = []) Api function to log a user on to the system
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    // ...
}
