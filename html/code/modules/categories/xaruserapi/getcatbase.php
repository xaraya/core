<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * Get a category base
 *
 * @param $args['bid'] base ID
 * @param $args['modid'] the id of the module (temporary)
 * @param $args['module'] the name of the module (temporary)
 * @param $args['itemtype'] the ID of the itemtype (temporary)
 * @returns details of a category base
 * @return category base
 */

/*
 * NOTE:
 * The modid and itemtype are only needed for the moment while
 * base IDs are not unique across the system.
 */

function categories_userapi_getcatbase($args)
{
    extract($args);

    $xartable = xarDB::getTables();
    sys::import('xaraya.structures.query');
    $q = new Query('SELECT', $xartable['categories_basecategories']);
    if (isset($module)) $q->eq('module_id',xarMod::getID($module));
    if (isset($itemtype)) $q->eq('itemtype',$itemtype);
    if (isset($id)) $q->eq('id',$id);
    if (isset($name)) $q->eq('name',$name);
//    $q->qecho(); exit;
    if (!$q->run()) return;
    return $q->row();
}

?>