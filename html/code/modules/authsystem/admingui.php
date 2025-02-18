<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Authsystem;

use Xaraya\Modules\AdminGuiClass;
use sys;

sys::import('xaraya.modules.admingui');

/**
 * Handle the authsystem admin GUI
 *
 * @method mixed createpassword(array $args = []) Function to create a password for a user
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
