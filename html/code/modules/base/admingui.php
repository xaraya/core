<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Base;

use Xaraya\Modules\AdminGuiClass;

/**
 * Handle the base admin GUI
 *
 * @method mixed composer(array $args = []) Manage third party libraries with composer
 * @method mixed confirmlogout(array $args = []) Confirm logout from administration system
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @method mixed release(array $args = []) View recent module releases via central repository
 * @method mixed sysinfo(array $args = []) Display some system information - This information can be used for support / debugging
 * @method mixed upgrade(array $args = []) Function to upgrade module
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
