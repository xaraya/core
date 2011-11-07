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
 * get info on a specific (list of) category
 * @param $args['cid'] id of category to get info, or
 * @param $args['cids'] array of category ids to get info
 * @returns array
 * @return category info array, or array of cat info arrays, false on failure
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
