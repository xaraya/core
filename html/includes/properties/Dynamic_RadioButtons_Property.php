<?php
/**
 * Dynamic Radio Buttons Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */

include_once "includes/properties/Dynamic_Select_Property.php";

class Dynamic_RadioButtons_Property extends Dynamic_Select_Property
{
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        $out = '';
        foreach ($options as $option) {
            $out .= '<input type="radio" name="'.$name.'" value="'.$option['id'].'"';
            if ($option['id'] == $value) {
                $out .= ' checked> '.$option['name'].' </input>';
            } else {
                $out .= '> '.$option['name'].' </input>';
            }
        }
        $out .= (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        return $out;
    }

    // default methods from Dynamic_Select_Property
}

?>