<?php
/**
 * Dynamic Select Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Select_Property extends Dynamic_Property
{
    var $options;

    function Dynamic_Select_Property($args)
    {
        $this->Dynamic_Property($args);
        if (!isset($this->options)) {
            $this->options = array();
        }
        if (count($this->options) == 0 && !empty($this->validation)) {

            // if the validation field starts with xarModAPIFunc, we'll assume that this is
            // a function call that returns an array of names, or an array of id => name
            if (preg_match('/^xarModAPIFunc/',$this->validation)) {
                eval('$options = ' . $this->validation .';');
                if (isset($options) && count($options) > 0) {
                    foreach ($options as $id => $name) {
                        array_push($this->options, array('id' => $id, 'name' => $name));
                    }
                }

            // or if it contains a ; we'll assume that this is a list of name1;name2;name3 or id1,name1;id2,name2;id3,name3
            } elseif (strchr($this->validation, ';')) {
                $options = explode(';', $this->validation);
                foreach ($options as $option) {
                    if (strchr($option, ',')) {
                        // if the option contains a , we'll assume it's an id,name combination
                        list($id,$name) = explode(',', $option);
                        array_push($this->options, array('id' => $id, 'name' => $name));
                    } else {
                        // otherwise we'll use the option for both id and name
                        array_push($this->options, array('id' => $option, 'name' => $option));
                    }
                }

            // otherwise we'll leave it alone, for use in any subclasses (e.g. min:max in NumberList below)
            } else {
            }
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        foreach ($this->options as $option) {
            if ($option['id'] == $value) {
                $this->value = $value;
                return true;
            }
        }
        $this->invalid = xarML('selection');
        $this->value = null;
        return false;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        $out = '<select' .
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               '>';
        foreach ($options as $option) {
            $out .= '<option';
            if (empty($option['id']) || $option['id'] != $option['name']) {
                $out .= ' value="'.$option['id'].'"';
            }
            if ($option['id'] == $value) {
                $out .= ' selected>'.$option['name'].'</option>';
            } else {
                $out .= '>'.$option['name'].'</option>';
            }
        }
        $out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        return $out;
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        $out = '';
    // TODO: support multiple selection
        $join = '';
        foreach ($this->options as $option) {
            if ($option['id'] == $value) {
                $out .= $join . xarVarPrepForDisplay($option['name']);
                $join = ' | ';
            }
        }
        return $out;
    }

}


?>