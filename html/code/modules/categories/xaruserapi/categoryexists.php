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
function categories_userapi_categoryexists( $args ) 
{

    extract($args);

    $path_array = explode("/", $path);

    $args = array();
    $cid = false;

    $maximum_depth = 2;
    $minimum_depth = 1;

    foreach ($path_array as $cat_name) {

        // Getting categories Array
        $categories = xarMod::apiFunc('categories','user','getcat',Array
            (
                'eid'           => false,
                'cid'           => $cid,
                'return_itself' => false,
                'getchildren'   => true,
                'maximum_depth' => $maximum_depth,
                'minimum_depth' => $minimum_depth
            ));
        foreach ($categories as $category) {
            if ($category['name'] == $cat_name) {
                //Found the category we are loking for
                array_shift($path_array);
                $cid = $category["cid"];
            }
        }

        $maximum_depth++;
        $minimum_depth++;
    }

    if (count($path_array) == 0) { return $cid; }
    
    return false;
}

?>
