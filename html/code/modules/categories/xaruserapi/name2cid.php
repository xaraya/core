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

/* test function for DMOZ-style short URLs in xaruser.php */

function categories_userapi_name2cid ($args)
{
    extract($args);
    sys::import('modules.categories.class.worker');
    $worker = new CategoryWorker();
    if (empty($name) || !is_numeric($name)) $cid = "Top";
    return $worker->name2id($name);
}

?>
