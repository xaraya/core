<?php

/* test function for DMOZ-style short URLs in xaruser.php */

function categories_userapi_name2cid ($args)
{
    extract($args);
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $categoriestable = $xartable['categories'];

    if (empty($name) || !is_string($name)) {
        $name = 'Top';
    }
    // for DMOZ-like URLs where the description contains the full path
    if (!empty($usedescr)) {
        $query = "SELECT parent_id, id FROM $categoriestable WHERE description = ?";
    } else {
        $query = "SELECT parent_id, id FROM $categoriestable WHERE name = ?";
    }
    $result = $dbconn->Execute($query,array($name));
    if (!$result) return;
    list($parent,$cid) = $result->fields;
    $result->Close();

    return $cid;
}

?>
