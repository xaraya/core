<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Roles;

use Xaraya\Modules\UserGuiClass;

/**
 * Handle the roles user GUI
 *
 * @method mixed account(array $args = []) Displays the dynamic user menu.
 * @method mixed changelanguage(array $args = []) Changes the navigation language - This is the external entry point to tell MLS use another language
 * @method mixed display(array $args = []) Display user
 * @method mixed email(array $args = []) Send email to a user
 * @method mixed getvalidation(array $args = []) getvalidation validates a new user into the system - if their status is set to xarRoles::ROLES_STATE_NOTVALIDATED.
 * @method mixed lostpassword(array $args = []) Sends a new password to the user if they have forgotten theirs.
 * @method mixed main(array $args = []) The main user interface function of this module.
 * @method mixed search(array $args = []) Search
 * @method mixed usermenu(array $args = []) Show the user menu
 * @method mixed view(array $args = [])
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    // ...
}
