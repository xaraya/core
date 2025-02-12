<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject;

use Xaraya\DataObject\Traits\AdminGuiInterface;
use Xaraya\DataObject\Traits\AdminGuiTrait;
use sys;

sys::import('modules.dynamicdata.traits.admingui');

/**
 * Handle (traditional) DD admin gui functions via module class
 * Note: this does not replace the object-centric UI handlers or direct use of object methods
 *
 * @method mixed access(array $args = []) Access control for objects - This is a standard function that is called whenever an administrator - wishes to modify the access to an object
 * @method mixed create(array $args = []) This is a standard function that is called with the results of the - form supplied by xarMod::guiFunc('dynamicdata','admin','new') to create a new item
 * @method mixed dbconfig(array $args = []) Database configurations used by modules and objects
 * @method mixed delete(array $args = []) delete item
 * @method mixed deleteStatic(array $args = [])
 * @method mixed deleteStaticTable(array $args = [])
 * @method mixed export(array $args = []) Export an object definition or an object item to XML
 * @method mixed form(array $args = []) add new item - This is a standard function that is called whenever an administrator - wishes to create a new module item
 * @method mixed import(array $args = []) Import an object definition or an object item from XML
 * @method mixed importpropertytypes(array $args = []) Import a property type
 * @method mixed importprops(array $args = []) Import the dynamic properties for a module + itemtype from a static table
 * @method mixed main(array $args = []) Main entry point for the admin interface of this module - This function is the default function for the admin interface, and is called whenever the module is - initiated with only an admin type but no func parameter passed.
 * @method mixed meta(array $args = []) Return meta data (test only)
 * @method mixed migrate(array $args = []) migrate module items
 * @method mixed modify(array $args = []) Modify an item - This is a standard function that is called whenever an administrator - wishes to modify a current module item
 * @method mixed modifyStatic(array $args = [])
 * @method mixed modifyconfig(array $args = []) Modify the configuration settings of this module - Standard GUI function to display and update the configuration settings of the module based on input data.
 * @method mixed modifyprop(array $args = []) Modify the dynamic properties for a module + itemtype
 * @method mixed new(array $args = []) Show add new item form - This is a standard function that is called whenever an administrator - wishes to create a new module item
 * @method mixed newStatic(array $args = [])
 * @method mixed orderprops(array $args = []) Re-order the dynamic properties for a module + itemtype
 * @method mixed privileges(array $args = []) Manage definition of instances for privileges (unfinished)
 * @method mixed query(array $args = []) query items
 * @method mixed relations(array $args = []) Return relationship information (test only)
 * @method mixed renameStaticTable(array $args = [])
 * @method mixed showpropval(array $args = []) Show configuration of some property
 * @method mixed testApis(array $args = []) Test APIs
 * @method mixed update(array $args = []) Update current item - This is a standard function that is called with the results of the - form supplied by xarMod::guiFunc('dynamicdata','admin','modify') to update a current item
 * @method mixed updatePropertydefs(array $args = []) Update configuration parameters of the module - This is a standard function to update the configuration parameters of the - module given the information passed back by the modification form
 * @method mixed updateprop(array $args = []) Update the dynamic properties for a module + itemtype
 * @method mixed utilities(array $args = []) Utilities
 * @method mixed view(array $args = []) View items
 * @method mixed viewPropertydefs(array $args = []) This is a standard function to modify the configuration parameters of the - module
 * @method mixed viewStatic(array $args = []) Return static table information
 * @extends
 */
class AdminGui implements AdminGuiInterface
{
    /** @use AdminGuiTrait<Module> */
    use AdminGuiTrait;
}
