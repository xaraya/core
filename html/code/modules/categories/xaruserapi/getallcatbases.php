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
 * get category bases
 *
 * @param $args['module'] the name of the module (optional)
 * @param $args['itemtype'] the ID of the itemtype (optional)
 * @returns array of category bases
 * @return list of category bases
 */

/*
 * Explanation of the output formats:
 * 'cids': an array of category ids only; zero-indexed numeric keys
 * 'tree': a comprehensive array of category base details; more information below
 * 'flat': an array of category-base arrays; zero-indexed numeric keys
 */

function categories_userapi_getallcatbases($args)
{
    extract($args);
    $xartable = xarDB::getTables();

// CHECKME: what about this old 'basecids' stuff ?
/*
    if (empty($itemtype)) {
        $cidstring = xarModVars::get($module,'basecids');
    } else {
        // FIXME: this doesn't work for itemtype == _XAR_ID_UNREGISTERED !
        $cidstring = xarModUserVars::get($module,'basecids',$itemtype);
    }
    if (!empty($cidstring)) {
        $rootcids = unserialize($cidstring);
    } else {
        $rootcids = array();
    }
*/

    sys::import('xaraya.structures.query');
    $q = new Query('SELECT');
    $q->addtable($xartable['categories_basecategories'],'base');
    $q->addtable($xartable['categories'],'category');
    $q->leftjoin('base.category_id','category.id');
    $q->addfield('base.id AS id');
    $q->addfield('base.category_id AS category_id');
    $q->addfield('base.name AS name');
    $q->addfield('base.module_id AS module_id');
    $q->addfield('base.itemtype AS itemtype');
    $q->addfield('category.left_id AS left_id');
    $q->addfield('category.right_id AS right_id');
    if (!empty($module))  $q->eq('module_id',xarMod::getID($module));
    if (!empty($module_id))  $q->eq('module_id',$module_id);
    if (isset($itemtype))  $q->eq('itemtype',$itemtype);
    $q->addorder('base.id');
//    $q->qecho();
    if (!$q->run()) return;
    return $q->output();
}

?>