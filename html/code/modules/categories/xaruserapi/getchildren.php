<?php

/**
 * get direct children of a specific (list of) category
 *
 * @param $args['cid'] id of category to get children for, or
 * @param $args['cids'] array of category ids to get children for
 * @param $args['return_itself'] =Boolean= return the cid itself (default false)
 * @returns array
 * @return array of category info arrays, false on failure
 */
function categories_userapi_getchildren($args)
{
    extract($args);

    if (!isset($cid) && !isset($cids)) {
       xarSession::setVar('errormsg', xarML('Bad arguments for API function'));
       return false;
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $categoriestable = $xartable['categories'];
    $bindvars = array();
    // TODO: simplify API by always using array of cids, optionally with one element
    $SQLquery = "SELECT id,
                        name,
                        description,
                        image,
                        parent_id,
                        left_id,
                        right_id
                   FROM $categoriestable ";
    if (isset($cid)) {
        $SQLquery .= "WHERE parent_id =?";
        $bindvars[] = $cid;
        if (!empty($return_itself)) {
            $SQLquery .= " OR id =?";
            $bindvars[] = $cid;
        }
    } else {
        $bindmarkers = '?' . str_repeat(',?',count($cids)-1);
        $allcids = join(', ',$cids);
        $SQLquery .= "WHERE parent_id IN ($bindmarkers)";
        $bindvars = $cids;
        if (!empty($return_itself)) {
            $SQLquery .= " OR id IN ($bindmarkers)";
            // bindvars could already hold the $cids
            $bindvars = array_merge($bindvars, $cids);
        }
    }
    $SQLquery .= " ORDER BY left_id";

    $result = $dbconn->Execute($SQLquery,$bindvars);
    if (!$result) return;

    $info = array();
    while (!$result->EOF) {
        list($cid, $name, $description, $image, $parent, $left, $right) = $result->fields;
        if (!xarSecurityCheck('ViewCategories',0,'Category',"$name:$cid")) {
             $result->MoveNext();
             continue;
        }
        $info[$cid] = Array(
                            "cid"         => $cid,
                            "name"        => $name,
                            "description" => $description,
                            "image"       => $image,
                            "parent"      => $parent,
                            "left"        => $left,
                            "right"       => $right
                           );
        $result->MoveNext();
    }
    return $info;
}

?>
