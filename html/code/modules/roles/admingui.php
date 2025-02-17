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

use Xaraya\Modules\AdminGuiClass;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.modules.adminapi');

/**
 * Handle the modules admin GUI
 *
 * @method mixed addmember(array $args = []) addMember - assign a user or group to a group - Make a user or group a member of another group.
 * @method mixed addprivilege(array $args = []) addprivilege - assign a privilege to role - This is an action page
 * @method mixed asknotification(array $args = []) Update users from roles_admin_showusers
 * @method mixed createmail(array $args = [])
 * @method mixed createpassword(array $args = []) createpassword - create a new password for the user
 * @method mixed delete(array $args = []) Delete a role - prompts for confirmation
 * @method mixed display(array $args = []) display role
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed modify(array $args = []) Modify role details
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @method mixed modifyemail(array $args = []) Modify the  email for users
 * @method mixed modifynotice(array $args = []) modify configuration
 * @method mixed new(array $args = []) Show new role form
 * @method mixed purge(array $args = []) purge users by status
 * @method mixed removemember(array $args = []) removeMember - remove a user or group from a group - Remove a user or group as a member of another group.
 * @method mixed removeprivilege(array $args = []) removeprivilege - remove a privilege - prompts for confirmation
 * @method mixed sendmail(array $args = []) Send mail
 * @method mixed showprivileges(array $args = []) showprivileges - display the privileges of this role
 * @method mixed showusers(array $args = []) Show users of this role
 * @method mixed sitelock(array $args = []) Site lock
 * @method mixed testprivileges(array $args = []) testprivileges - test a user or group's privileges against a mask - Performs a test of all the privileges of a user or group against a security mask.
 * @method mixed updatestate(array $args = []) Update users from roles_admin_showusers
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
