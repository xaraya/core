<?php
/**
 * Dynamic Group List Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_UserList_Property.php";

/**
 * handle the grouplist property
 *
 * @package dynamicdata
 *
 */
class Dynamic_GroupList_Property extends Dynamic_UserList_Property
{

    function Dynamic_GroupList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        $this->roles = xarModAPIFunc('roles', 'user', 'getallgroups');
    }

}

?>
