<?php
/**
 * Dynamic Time Zone Property
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
 * handle the timezone property
 *
 * @package dynamicdata
 */
class Dynamic_TimeZone_Property extends Dynamic_Select_Property
{
    function Dynamic_TimeZone_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $this->options = array(
                                 array('id' => -12, 'name' => xarML('GMT #(1)','-12:00')),
                                 array('id' => -11, 'name' => xarML('GMT #(1)','-11:00')),
                                 array('id' => -10, 'name' => xarML('GMT #(1)','-10:00')),
                                 array('id' => -9, 'name' => xarML('GMT #(1)','-09:00')),
                                 array('id' => -8, 'name' => xarML('GMT #(1)','-08:00')),
                                 array('id' => -7, 'name' => xarML('GMT #(1)','-07:00')),
                                 array('id' => -6, 'name' => xarML('GMT #(1)','-06:00')),
                                 array('id' => -5, 'name' => xarML('GMT #(1)','-05:00')),
                                 array('id' => -4, 'name' => xarML('GMT #(1)','-04:00')),
                                 array('id' => -3.5, 'name' => xarML('GMT #(1)','-03:30')),
                                 array('id' => -3, 'name' => xarML('GMT #(1)','-03:00')),
                                 array('id' => -2, 'name' => xarML('GMT #(1)','-02:00')),
                                 array('id' => -1, 'name' => xarML('GMT #(1)','-01:00')),
                                 array('id' => '0', 'name' => xarML('GMT')),
                                 array('id' => 1, 'name' => xarML('GMT #(1)','+01:00')),
                                 array('id' => 2, 'name' => xarML('GMT #(1)','+02:00')),
                                 array('id' => 3, 'name' => xarML('GMT #(1)','+03:00')),
                                 array('id' => 3.5, 'name' => xarML('GMT #(1)','+03:30')),
                                 array('id' => 4, 'name' => xarML('GMT #(1)','+04:00')),
                                 array('id' => 4.5, 'name' => xarML('GMT #(1)','+04:30')),
                                 array('id' => 5, 'name' => xarML('GMT #(1)','+05:00')),
                                 array('id' => 5.5, 'name' => xarML('GMT #(1)','+05:30')),
                                 array('id' => 6, 'name' => xarML('GMT #(1)','+06:00')),
                                 array('id' => 6.5, 'name' => xarML('GMT #(1)','+06:30')),
                                 array('id' => 7, 'name' => xarML('GMT #(1)','+07:00')),
                                 array('id' => 8, 'name' => xarML('GMT #(1)','+08:00')),
                                 array('id' => 9, 'name' => xarML('GMT #(1)','+09:00')),
                                 array('id' => 9.5, 'name' => xarML('GMT #(1)','+09:30')),
                                 array('id' => 10, 'name' => xarML('GMT #(1)','+10:00')),
                                 array('id' => 11, 'name' => xarML('GMT #(1)','+11:00')),
                                 array('id' => 12, 'name' => xarML('GMT #(1)','+12:00')),
                                 array('id' => 13, 'name' => xarML('GMT #(1)','+13:00')),
                             );
        }
    }

    // default methods from Dynamic_Select_Property

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
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $out = '<select' .
               ' name="' . $name . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               '>';
        $now = time();
        foreach ($options as $option) {
            $out .= '<option';
            if (empty($option['id']) || $option['id'] != $option['name']) {
                $out .= ' value="'.$option['id'].'"';
            }
            $time = gmdate('H:i',$now + $option['id']*60*60);
            if ($option['id'] == $value) {
                $out .= ' selected="selected">'.$time.' ('.$option['name'].')</option>';
            } else {
                $out .= '>'.$time.' ('.$option['name'].')</option>';
            }
        }
        $out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        return $out;
    }

}

?>
