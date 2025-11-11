<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Privileges;

use Xaraya\Modules\AdminGuiClass;

/**
 * Handle the privileges admin GUI
 *
 * @method mixed addmember(array $args = []) addMember - assign a privilege as a member of another privilege - Make a privilege a member of another privilege.
 * @method mixed addprivilege(array $args = []) addPrivilege - add a privilege to the repository - This is an action page
 * @method mixed assignprivileges(array $args = [])
 * @method mixed deleteprivilege(array $args = []) deletePrivilege - delete a privilege - prompts for confirmation
 * @method mixed deleterealm(array $args = []) deleteRealm - delete a realm - prompts for confirmation
 * @method mixed displayprivilege(array $args = []) displayprivilege - display privilege details
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @method mixed modifyprivilege(array $args = []) modifyprivilege - modify privilege details
 * @method mixed modifyrealm(array $args = []) modifyRealm - modify an existing realm
 * @method mixed new(array $args = []) new - create a new privilege - Takes no parameters
 * @method mixed newrealm(array $args = []) addRealm - create a new realm
 * @method mixed removebranch(array $args = []) removebranch - remove a privilege from a privilege - Remove a privilege as a member of another privilege.
 * @method mixed removemember(array $args = []) removeMember - remove a privilege from a privilege - Remove a privilege as a member of another privilege.
 * @method mixed removerole(array $args = []) removeRole - remove a role from a privilege assignment - prompts for confirmation
 * @method mixed updateprivilege(array $args = []) updateprivilege - update a privilege
 * @method mixed viewprivileges(array $args = []) viewPrivileges - view the current privileges
 * @method mixed viewrealms(array $args = []) viewRealms - view the defined realms
 * @method mixed viewroles(array $args = []) viewroles - display the roles this privilege is assigned to
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
