<?php
/**
 * Dynamic Date Format Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * Class for the date format property
 *
 * @package dynamicdata
 */
class Dynamic_DateFormat_Property extends Dynamic_Select_Property
{
    function Dynamic_DateFormat_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => 0, 'name' => xarML('d M Y H:i')),
                                 array('id' => 1, 'name' => xarML('TODO')),
                             );
        }
    }

    // default methods from Dynamic_Select_Property
}

?>