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

use Xaraya\Modules\AdminGuiClass;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.modules.adminapi');

/**
 * Handle the modules admin GUI
 *
 * @method mixed activate(array $args = []) Activate a module
 * @method mixed aliases(array $args = [])
 * @method mixed confirmlogout(array $args = []) Confirm logout from administration system
 * @method mixed deactivate(array $args = [])
 * @method mixed hooks(array $args = []) Configure hooks by hook module
 * @method mixed install(array $args = [])
 * @method mixed installall(array $args = []) Installs a module - Loads module admin API and calls the initialise - function to actually perform the initialisation, - then redirects to the list function with a - status message and returns true.
 * @method mixed list(array $args = []) List modules and current settings
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed modify(array $args = []) Modify module settings - This function queries the database for - the module's information and then queries - for any hooks that the module could use - and passes the data to the template.
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @method mixed modifyinstalloptions(array $args = [])
 * @method mixed modifyproperties(array $args = []) Modify module properties - This function queries the database for - the module's information - and passes the data to the template.
 * @method mixed modinfo(array $args = []) View complete module information/details - function passes the data to the template - opens in new window when browser is javascript enabled
 * @method mixed prefs(array $args = []) Set preferences for modules module
 * @method mixed regenerate(array $args = []) Regenerate list of available modules - Loads module admin API and calls the regenerate function - to actually perform the regeneration, then redirects - to the list function with a status meessage and returns true.
 * @method mixed release(array $args = []) View recent module releases via central repository
 * @method mixed remove(array $args = []) Remove a module - Loads module admin API and calls the remove function - to actually perform the removal, then redirects to - the list function with a status message and retursn true.
 * @method mixed settings(array $args = []) List modules and current settings
 * @method mixed tools(array $args = []) Tools to build and verify modules elements
 * @method mixed update(array $args = []) Update a module
 * @method mixed updatehooks(array $args = []) Update hooks by hook module
 * @method mixed updateinstalloptions(array $args = [])
 * @method mixed updateproperties(array $args = []) Update a module
 * @method mixed updateversion(array $args = []) Update the module version in the database
 * @method mixed upgrade(array $args = []) Upgrade a module - Loads module admin API and calls the upgrade function - to actually perform the upgrade, then redrects to - the list function and with a status message and returns - true.
 * @method mixed view(array $args = []) List modules and current settings
 * @method mixed viewerror(array $args = []) View an error with a module
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
