<?php
/**
 * Dynamic Field Type Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */

include_once "includes/properties/Dynamic_Select_Property.php";

class Dynamic_FieldType_Property extends Dynamic_Select_Property
{
    function Dynamic_FieldType_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $proptypes = Dynamic_Property_Master::getPropertyTypes();
            if (!isset($proptypes)) {
                $proptypes = array();
            }
            foreach ($proptypes as $propid => $proptype) {
                $this->options[] = array('id' => $propid, 'name' => $proptype['label']);
            }
        }
    }

    // default methods from Dynamic_Select_Property
}

?>