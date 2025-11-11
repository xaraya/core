<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Themes;

use Xaraya\Modules\AdminGuiClass;

/**
 * Handle the themes admin GUI
 *
 * @method mixed activate(array $args = []) Activate a theme - Loads theme admin API and calls the activate - function to actually perform the activation, - then redirects to the list function with a - status message and returns true.
 * @method mixed cacheview(array $args = [])
 * @method mixed corecssupdate(array $args = []) Module admin function to update configuration Xaraya core CSS
 * @method mixed cssconfig(array $args = []) Module admin function to review and configure Xaraya CSS
 * @method mixed deactivate(array $args = []) Deactivate a theme - Loads theme admin API and calls the setstate - function    to actually    perfrom    the    deactivation, - then    redirects to the list function with    a status - message and returns true.
 * @method mixed deleteConfig(array $args = [])
 * @method mixed displayConfig(array $args = [])
 * @method mixed exportConfig(array $args = [])
 * @method mixed initialise(array $args = []) Initialise a theme - Loads theme admin API and calls the initialise - function to actually perform the initialisation, - then redirects to the list function with a - status message and returns true.
 * @method mixed install(array $args = []) Installs a theme - Loads module themes API and calls the initialise - function to actually perform the initialisation, - then redirects to the list function with a - status message and returns true.
 * @method mixed list(array $args = []) List themes and current settings
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @method mixed newConfig(array $args = [])
 * @method mixed regenerate(array $args = []) Regenerate list of available themes - Loads theme admin API and calls the regenerate function - to actually perform the regeneration, then redirects - to the list function with a status meessage and returns true.
 * @method mixed release(array $args = []) View recent module releases via central repository
 * @method mixed remove(array $args = []) Remove a theme - Loads theme admin API and calls the remove function - to actually perform the removal, then redirects to - the list function with a status message and retursn true.
 * @method mixed setdefault(array $args = []) Default theme for site - Sets the module var for the default site theme.
 * @method mixed settings(array $args = []) List themes and current settings
 * @method mixed themesinfo(array $args = []) View complete theme information/details - function passes the data to the template
 * @method mixed updateConfig(array $args = [])
 * @method mixed upgrade(array $args = []) Upgrade a theme - Loads theme admin API and calls the upgrade function - to actually perform the upgrade, then redrects to - the list function and with a status message and returns - true.
 * @method mixed view(array $args = []) List themes and current settings
 * @method mixed viewConfigs(array $args = [])
 * @method mixed viewCsslibs(array $args = [])
 * @method mixed viewJslibs(array $args = [])
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
