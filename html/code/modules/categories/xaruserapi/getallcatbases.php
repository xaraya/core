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
    sys::import('modules.categories.class.worker');
    $worker = new CategoryWorker();
    $bases = $worker->getcatbases($args);
    return $bases;
}

?>