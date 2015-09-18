<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * Get direct children of a specific (list of) category
 *
 * @param $args['cid'] id of category to get children for, or
 * @param $args['cids'] array of category ids to get children for
 * @param $args['return_itself'] =Boolean= return the cid itself (default false)
 * @return array Return array of category info arrays, false on failure
 */
function categories_userapi_getchildren($args)
{
    extract($args);

    if (!isset($cid) && !isset($cids)) {
       xarSession::setVar('errormsg', xarML('Bad arguments for API function'));
       return false;
    }
    $myself = isset($args['return_itself']) ? $args['return_itself'] : 0;
    sys::import('modules.categories.class.worker');
    $worker = new CategoryWorker();
    if (isset($cid)) $children = $worker->getchildren($cid, $myself);
    else $children = $worker->getchildren($cids, $myself);
    return $children;
}

?>
