<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories;

use Xaraya\Modules\UserApiClass;

/**
 * Handle the categories user API
 *
 * @method mixed countcats(array $args = []) Count number of categories (optionally below some category) - Usage : $num = xar::mod()->apiFunc('categories', 'user', 'countcats', $cat); -         $total = xar::mod()->apiFunc('categories', 'user', 'countcats', array());
 * @method mixed countitems(array $args = []) Count number of items
 * @method mixed deepcount(array $args = []) Count number of items per category, or number of categories for each item
 * @method mixed getallcatbases(array $args = []) get category bases
 * @method mixed getancestors(array $args = []) * Get ancestors (starting with parent, working towards root) of a specific - [list of] category. This function used to be 'getparents', the new name - being less ambiguous (see XLST AxisNames for examples).
 * @method mixed getcat(array $args = []) Get categories - Examples: - getcat() => Return all the categories - getcat(Array('cid' -> ID)) => Only cid and its children, grandchildren and -                                every other sibbling will be returned - getcat(Array('eid' -> ID)) => All categories will be returned EXCEPT -                                eid and its children, grandchildren and -                                every other sibbling will be returned
 * @method mixed getcatinfo(array $args = []) Get info on a specific (list of) category
 * @method mixed getcatinfotag(array $args = []) Handle <xar:categories-catinfo ...> template tags - Format : <xar:categories-catinfo module="modulename" itemtype="itemtype" itemid="itemid" base="base-cat-id"/> - Default module is the module in which the template tag is called.
 * @method mixed getchildren(array $args = []) Get direct children of a specific (list of) category
 * @method mixed getitemcats(array $args = []) Get an array of assigned category details for a specific item, limiting by a base cid if required.
 * @method mixed getitemlinks(array $args = []) Utility function to pass individual item links to whoever
 * @method mixed getitemtypes(array $args = []) Utility function to retrieve the list of item types of this module (if any)
 * @method mixed getlinkage(array $args = []) Fetches category linkage from database
 * @method mixed getlinkages(array $args = []) Get links
 * @method mixed getlinks(array $args = []) Get links
 * @method mixed getmodules(array $args = []) Get the list of modules and itemtypes for which we're categorising items
 * @method mixed getneighbours(array $args = []) Get info on neighbours based on left/right numbers - (easiest is to pass it a category array coming from getcat*)
 * @method mixed getorphanlinks(array $args = []) Get orphan links
 * @method mixed getparents(array $args = []) Get parents of a specific (list of) category
 * @method mixed groupcount(array $args = []) Count number of items per category, or number of categories for each item
 * @method mixed isdescendant(array $args = []) Checks whether one or more cid is a descendant of one or more category - tree branches. Returns true if any cid is a descendant of any branch.
 * @method mixed leftjoin(array $args = []) Return the field names and correct values for joining on categories table - example : SELECT ..., $cid, ... -           FROM ... -           LEFT JOIN $table -               ON $field = <name of itemid field in your module> -           $more -           WHERE ... -               AND $where // this includes module_id = <your module ID>
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    // ...
}
