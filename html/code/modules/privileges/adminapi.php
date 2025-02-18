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

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the privileges admin API
 *
 * @method mixed get(array $args = []) Get a specific privilege - Transient hack, will be removed
 * @method mixed getcomponents(array $args = []) getcomponents: returns all the current components of a module.
 * @method mixed getinstances(array $args = []) getinstances: returns all the current privilege instances of a module/component combination.
 * @method mixed menu(array $args = []) generate the common admin menu configuration
 * @method mixed removemember(array $args = []) removeMember - remove a privilege from a privilege - Remove a privilege as a member of another privilege.
 * @method mixed returnprivilege(array $args = []) returnPrivilege: adds or modifies a privilege coming from an external wizard .
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
