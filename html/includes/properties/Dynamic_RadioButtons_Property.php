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
    // CHECKME: can you use id="..." here ? For the first radio-button perhaps ?
        foreach ($options as $option) {
            $out .= '<input type="radio" name="'.$name.'" value="'.$option['id'].'"';
            if ($option['id'] == $value) {
                $out .= ' checked="checked"> '.$option['name'].' </input>';
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
