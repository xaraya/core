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
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Get info on a specific (list of) category
 * @param $args['cid'] id of category to get info, or
 * @param $args['cids'] array of category ids to get info
 * @return array Returns category info array, or array of cat info arrays, false on failure
 */
function categories_userapi_getcatinfo($args)
{
    extract($args);

    if (!isset($cid) && !isset($cids)) {
       xarSession::setVar('errormsg', xarML('Bad arguments for API function'));
       return false;
    }

    if (empty($cid) && empty($cids)) {
       // nothing to see here, return empty catinfo array
       return array();
    }

    sys::import('modules.categories.class.worker');
    $worker = new CategoryWorker();
    if (isset($cid)) $info = $worker->getcatinfo($cid);
    else $info = $worker->getcatinfo($cids);
    return $info;
}

?>
