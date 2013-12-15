<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * get category bases
 *
 * @param $args['object'] the name of the object (required)
 * @param $args['property'] the name of the categories property of the object (optional)
 * @return array of category bases
 */

function categories_userapi_getallcatbases($args)
{
    sys::import('modules.categories.class.worker');
    $worker = new CategoryWorker();
    $bases = $worker->getcatbases($args);
    return $bases;
}

?>