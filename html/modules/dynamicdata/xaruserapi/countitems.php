<?php

/**
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @param $args the usual suspects :)
 * @returns integer
 * @return number of items held by this module
 */
function dynamicdata_userapi_countitems($args)
{
    $mylist = new Dynamic_Object_List($args);
    if (!isset($mylist)) return;

    return $mylist->countItems();
}

?>
