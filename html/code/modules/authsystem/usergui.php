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

use Xaraya\Modules\UserGuiClass;

/**
 * Handle the authsystem user GUI
 *
 * @method mixed login(array $args = []) Log a user into the system
 * @method mixed logout(array $args = []) Log a user out of the system.
 * @method mixed main(array $args = []) The main user interface function of this module.
 * @method mixed password(array $args = []) Display a password request page.
 * @method mixed showloginform(array $args = []) Shows the user login form when login block is not active
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    // ...
}
