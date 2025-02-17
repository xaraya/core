<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the modules user API
 *
 * @method mixed addmember(array $args = []) addmember - add a role to a group
 * @method mixed checkprivilege(array $args = [])
 * @method mixed countall(array $args = []) count all users
 * @method mixed countallactive(array $args = []) Count all active users
 * @method mixed countgroups(array $args = []) utility function to count the number of items held by this module
 * @method mixed countitems(array $args = []) utility function to count the number of users
 * @method mixed get(array $args = []) get a specific user by any of his attributes - uname, id and email are guaranteed to be unique, - otherwise the first hit will be returned
 * @method mixed getactive(array $args = []) check if a user is active or not on the site
 * @method mixed getall(array $args = []) get all users
 * @method mixed getallactive(array $args = []) get all active users
 * @method mixed getallgroups(array $args = []) viewallgroups - generate all groups listing.
 * @method mixed getallroles(array $args = []) get all roles
 * @method mixed getancestors(array $args = []) getancestors - get ancestors of a role
 * @method mixed getdefaultauthdata(array $args = []) getdefaultauthdata  - get the default authentication module date from roles - The login and logout may not be supplied by the authentication module and so could be different
 * @method mixed getdefaultregdata(array $args = []) getdefaultregdata  - get the default registration module data
 * @method mixed getdeleteduser(array $args = []) get a specific deleted user by any of his attributes - uname, id and email are guaranteed to be unique, - otherwise the first hit will be returned
 * @method mixed getitemlinks(array $args = []) utility function to pass individual item links to whoever
 * @method mixed getitemtypes(array $args = []) Utility function to retrieve the list of itemtypes of this module (if any).
 * @method mixed getmenulinks(array $args = []) Utility function pass individual menu items to the user menu.
 * @method mixed getprimaryparent(array $args = [])
 * @method mixed getstates(array $args = []) Get States
 * @method mixed getuserhome(array $args = [])
 * @method mixed getusers(array $args = []) getUsers - view users in group
 * @method mixed leftjoin(array $args = []) return the field names and correct values for joining on users table - example : SELECT ..., $name, $email,... -           FROM ... -           LEFT JOIN $table -               ON $field = <name of userid field> -           WHERE ... -               AND $email LIKE '%xaraya.com' -               AND $where
 * @method mixed makepass(array $args = [])
 * @method mixed parseuserhome(array $args = [])
 * @method mixed removemember(array $args = []) removemember - remove a role from a group
 * @method mixed updatestatus(array $args = []) Update a users status
 * @method mixed usermenu(array $args = []) Provides extra processing to roles user account function for user_settings
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    // ...
}
