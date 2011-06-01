<?php

/**
 * creates a category using the parent model
 *
 *  -- INPUT --
 * @param $args['name'] the name of the category
 * @param $args['description'] the description of the category
 * @param $args['image'] the (optional) image for the category
 * @param $args['parent_id'] Parent Category ID (0 if root)
 *
 *  -- OUTPUT --
 * @returns int
 * @return category ID on success, false on failure
 */
function categories_adminapi_create ($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if ((!isset($name))        ||
        (!isset($description)) ||
        (!isset($parent_id))   ||
        (!is_numeric($parent_id))
       )
    {
        $msg = xarML('Invalid Parameter Count in  categories_adminapi_create');
        throw new BadParameterException(null, $msg);
    }

    if (!isset($image)) $image = '';
    if (!isset($child_object)) $child_object = '';

    // Security check
    // Has to be redone later

    if(!xarSecurityCheck('AddCategories')) return;

    if ($parent_id != 0)
    {
       $cat = xarMod::apiFunc('categories', 'user', 'getcatinfo', Array('cid'=>$parent_id));

       if ($cat == false)
       {
            $msg = "Unable to load the categories module´s user API";
            throw new BadParameterException(null, $msg);
       }
//       $point_of_insertion = $cat['left'] + 1;
        $point_of_insertion = $cat['right'];
    } else {
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $categoriestable = $xartable['categories'];
        $query = "SELECT MAX(right_id) FROM " . $categoriestable;
        $result = $dbconn->Execute($query);
        if (!$result) return;

        if (!$result->EOF) {
            list($max) = $result->fields;
            $point_of_insertion = $max + 1;
        } else {
            $point_of_insertion = 1;
        }
    }
    return xarMod::apiFunc('categories','admin','createcatdirectly',Array(
                    'point_of_insertion' => $point_of_insertion,
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                    'parent' => $parent_id,
                    'child_object' => $child_object
                )
            );
}

?>