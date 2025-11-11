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

namespace Xaraya\Modules\DynamicData;

use Xaraya\Modules\DynamicData\Traits\AdminApiInterface;
use Xaraya\Modules\DynamicData\Traits\AdminApiTrait;

/**
 * Handle (traditional) DD admin api functions via module class
 * Note: this does not replace the direct use of object methods
 *
 * @method mixed browse(array $args = [])
 * @method mixed create(array $args = []) create a new item (the whole item or some dynamic data fields for it)
 * @method mixed createobject(array $args = []) create a new dynamic object
 * @method mixed createproperty(array $args = []) create a new property field for an object
 * @method mixed delete(array $args = []) delete an item (the whole item or the dynamic data fields of it)
 * @method mixed deleteobject(array $args = []) delete a dynamic object and its properties
 * @method mixed deleteprop(array $args = []) delete a property field
 * @method mixed getnextitemtype(array $args = []) get the next itemtype of objects pertaining to a given module
 * @method mixed importpropertytypes(array $args = []) Check the properties directory for properties and import them into the Property Type table.
 * @method mixed menu(array $args = []) generate the common admin menu configuration
 * @method mixed showfilterform(array $args = []) Show an input form in a template
 * @method mixed showform(array $args = []) Show an input form in a template
 * @method mixed showinput(array $args = []) show some predefined form input field in a template
 * @method mixed update(array $args = []) update an item (the whole item or the dynamic data fields of it)
 * @method mixed updateprop(array $args = []) update a property field
 * @extends
 */
class AdminApi implements AdminApiInterface
{
    /** @use AdminApiTrait<Module> */
    use AdminApiTrait;
}
