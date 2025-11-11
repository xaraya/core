<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Mail;

use Xaraya\Modules\AdminGuiClass;

/**
 * Handle the mail admin GUI
 *
 * @method mixed compose(array $args = []) Test the email settings
 * @method mixed create(array $args = [])
 * @method mixed createq(array $args = [])
 * @method mixed createqdef(array $args = [])
 * @method mixed delete(array $args = [])
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed mapping(array $args = [])
 * @method mixed modify(array $args = [])
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @method mixed new(array $args = [])
 * @method mixed qstatus(array $args = [])
 * @method mixed sendtest(array $args = []) Test the email settings
 * @method mixed template(array $args = []) Modify the email templates for hooked notifications
 * @method mixed update(array $args = [])
 * @method mixed view(array $args = []) Queue management for mail module
 * @method mixed viewq(array $args = []) View the current mail queue (if any)
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
