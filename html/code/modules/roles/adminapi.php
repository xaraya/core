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

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the roles admin API
 *
 * @method mixed addmember(array $args = []) insertuser - add a user to a group
 * @method mixed getgroupmenulinks(array $args = []) utility function pass individual menu items to the main menu
 * @method mixed getmessageincludestring(array $args = [])
 * @method mixed getmessagestrings(array $args = [])
 * @method mixed menu(array $args = [])
 * @method mixed purge(array $args = []) delete users based on status
 * @method mixed recall(array $args = [])
 * @method mixed senduseremail(array $args = []) Send emails to users by mailtype - Ex: Lost Password, Confirmation
 * @method mixed stateupdate(array $args = []) Update a user's state
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
