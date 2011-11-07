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

function categories_userapi_cid2name ($args)
{
    extract($args);
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $categoriestable = $xartable['categories'];

    if (empty($cid) || !is_numeric($cid)) {
        $cid = 1;
    }
    // for DMOZ-like URLs where the description contains the full path
    if (!empty($usedescr)) {
        $query = "SELECT parent_id, description FROM $categoriestable WHERE id = ?";
    } else {
        $query = "SELECT parent_id, name FROM $categoriestable WHERE id = ?";
    }
    $result = $dbconn->Execute($query,array($cid));
    if (!$result) return;

    list($parent,$name) = $result->fields;
    $result->Close();

    $name = rawurlencode($name);
    $name = preg_replace('/%2F/','/',$name);
    return $name;
}

?>
