<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Create a new category
 *
 */
function categories_adminapi_create($args)
{
    // Make sure we have all the required values
    if (empty($args['name'])) $args['name'] = xarML('New Category');
    // This makes the root category to be the parent of this new one
    if (empty($args['parent_id'])) $args['parent_id'] = 1;
    // This makes the relative position of this category the last child of the parent
    if (empty($args['relative_position'])) $args['relative_position'] = 3;
    
    sys::import('modules.dynamicdata.class.objects.master');
    $category = DataObjectMaster::getObject(array('name' => 'categories'));
    $id = $category->createItem($args);
    return $id;
}