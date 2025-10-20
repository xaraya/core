<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules;

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the modules admin API
 *
 * @method mixed activate(array $args = []) Activate a module if it has an active function, otherwise just set the state to active
 * @method mixed addModuleAlias(array $args = []) define a module name as an alias for some other module - (only used for short URL support at the moment)
 * @method mixed checkversion(array $args = []) Checks for change in module versions, and updates the status of them if any is found
 * @method mixed countitems(array $args = [])
 * @method mixed deactivate(array $args = []) Deactivate a module if it has a deactive function, otherwise just set the state to deactive
 * @method mixed deleteModuleAlias(array $args = []) remove an alias for a module name - (only used for short URL support at the moment)
 * @method mixed disablehooks(array $args = []) Disable hooks between a caller module and a hook module - Note : generic hooks will not be disabled if a specific item type is given
 * @method mixed enablehooks(array $args = []) Enable hooks between a caller module and a hook module - Note : hooks will be enabled for all item types if no specific item type is given
 * @method mixed executeinitfunction(array $args = []) Loads xarinit.php file or module installer class and executes the given function
 * @method mixed getdbmodules(array $args = []) Get all modules in the database
 * @method mixed geteventhandlers(array $args = []) Get the list of active event handlers @deprecated 2.4.0 replaced with xarEvent code and event observers
 * @method mixed getfilemodules(array $args = []) Get module information from xarversion.php for each module - Here we cycle through the modules directory and and - return an array of information from xarversion.php of each module.
 * @method mixed gethookedmodules(array $args = []) Get list of modules calling a particular hook module
 * @method mixed gethooklist(array $args = []) Obtain list of hooks (optionally for a particular module)
 * @method mixed getitems(array $args = [])
 * @method mixed getlist(array $args = []) Get a list of modules that matches required criteria.
 * @method mixed initialise(array $args = []) Initialise a module
 * @method mixed list(array $args = []) Obtain list of modules (deprecated)
 * @method mixed regenerate(array $args = []) Regenerate module list
 * @method mixed remove(array $args = []) Remove a module
 * @method mixed removemissing(array $args = []) Remove a module when the files are missing
 * @method mixed setPrefDefaults(array $args = []) reset admin preferences to default module preferences
 * @method mixed setstate(array $args = []) Set the state of a module
 * @method mixed standarddeinstall(array $args = []) Perform standard module removal actions
 * @method mixed standardinstall(array $args = [])
 * @method mixed update(array $args = []) Update module information
 * @method mixed updatehooks(array $args = []) Update hooks for a particular hook module
 * @method mixed updateproperties(array $args = []) Update module information
 * @method mixed updateversion(array $args = []) Update the module version in the database
 * @method mixed upgrade(array $args = []) Upgrade a module
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
