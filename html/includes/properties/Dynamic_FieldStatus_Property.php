<?php
/**
 * Dynamic Field Status Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */

include_once "includes/properties/Dynamic_Select_Property.php";

class Dynamic_FieldStatus_Property extends Dynamic_Select_Property
{
    function Dynamic_FieldStatus_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => 0, 'name' => xarML('Disabled')),
                                 array('id' => 1, 'name' => xarML('Active')),
                                 array('id' => 2, 'name' => xarML('Display Only')),
                             );
        }
    }

    // default methods from Dynamic_Select_Property
}

?>