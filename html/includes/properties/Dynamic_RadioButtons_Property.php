<?php
/**
 * Dynamic Radio Buttons Property
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
 * handle radio buttons property
 *
 * @package dynamicdata
 */
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
        $idx = 1;
        foreach ($options as $option) {
            $out .= '<input type="radio" name="'.$name.'" id="'.$name.'_'.$idx.'" value="'.$option['id'].'"';
            if ($option['id'] == $value) {
                $out .= ' checked="checked">';
            } else {
                $out .= '>';
            }
            $out .= '<label for="'.$name.'_'.$idx.'"> '.$option['name'].' </label></input>';
            $idx++;
        }
        $out .= (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        return $out;
    }

    // default methods from Dynamic_Select_Property
}

?>