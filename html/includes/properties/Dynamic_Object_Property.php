<?php
/**
 * Dynamic Object Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */

include_once "includes/properties/Dynamic_Select_Property.php";

class Dynamic_Object_Property extends Dynamic_Select_Property
{
    function Dynamic_Object_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $objects =& Dynamic_Object_Master::getObjects();
            if (!isset($objects)) {
                $objects = array();
            }
            foreach ($objects as $objectid => $object) {
                $this->options[] = array('id' => $objectid, 'name' => $object['name']);
            }
        }
    }

    // default methods from Dynamic_Select_Property
}

?>