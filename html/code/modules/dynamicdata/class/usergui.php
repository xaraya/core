<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject;

use Xaraya\DataObject\Traits\UserGuiInterface;
use Xaraya\DataObject\Traits\UserGuiTrait;
use sys;

sys::import('modules.dynamicdata.class.traits.usergui');

/**
 * Handle (traditional) DD user gui functions via module class
 * Note: this does not replace the object-centric UI handlers or direct use of object methods
 *
 * @method mixed display(array $args = []) display an item - This is a standard function to provide detailed informtion on a single item - available from the module.
 * @method mixed filtertag(array $args = [])
 * @method mixed main(array $args = []) The main user interface function of this module.
 * @method mixed property(array $args = []) Execute a function in a standalone property
 * @method mixed search(array $args = []) search dynamicdata (called as hook from search module, or directly with pager)
 * @method mixed view(array $args = []) view a list of items - This is a standard function to provide an overview of all of the items - available from the module.
 * @extends
 */
class UserGui implements UserGuiInterface
{
    /** @use UserGuiTrait<Module> */
    use UserGuiTrait;
}
