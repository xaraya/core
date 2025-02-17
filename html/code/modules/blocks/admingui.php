<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Blocks;

use Xaraya\Modules\AdminGuiClass;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.modules.adminapi');

/**
 * Handle the modules admin GUI
 *
 * @method mixed deleteInstance(array $args = []) Delete a block instance
 * @method mixed deleteType(array $args = []) Function to delete type
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed modifyInstance(array $args = []) Modify a block instance
 * @method mixed modifyType(array $args = [])
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @method mixed newInstance(array $args = []) Display type options, form and add a new block instance to the system
 * @method mixed refreshTypes(array $args = [])
 * @method mixed viewInstances(array $args = []) View block instances
 * @method mixed viewTypes(array $args = []) View block types
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    use OtherApiTrait;
    // ...
}
