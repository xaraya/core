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

/* test function for DMOZ-style short URLs in xaruser.php */

function categories_userapi_cid2name ($args)
{
    extract($args);
    sys::import('modules.categories.class.worker');
    $worker = new CategoryWorker();
    if (empty($cid) || !is_numeric($cid)) $cid = 0;
    return $worker->id2name($cid);
}

?>
