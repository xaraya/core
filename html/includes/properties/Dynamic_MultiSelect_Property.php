<?php
/**
 * Dynamic Multi Select Property (saved as serialized array internally)
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * handle the multiselect property
 *
 * @package dynamicdata
 */
class Dynamic_MultiSelect_Property extends Dynamic_Select_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        $validlist = array();
        foreach ($this->options as $option) {
            array_push($validlist,$option['id']);
        }
        foreach ($value as $val) {
            if (!in_array($val,$validlist)) {
                $this->invalid = xarML('selection');
                $this->value = null;
                return false;
            }
        }
        $this->value = serialize($value);
        return true;
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
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        $out = '<select' .
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '[]"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               ' multiple>';
        foreach ($options as $option) {
            $out .= '<option';
            if (empty($option['id']) || $option['id'] != $option['name']) {
                $out .= ' value="'.$option['id'].'"';
            }
            if (in_array($option['id'],$value)) {
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
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        $out = '';
        $join = '';
        foreach ($this->options as $option) {
            if (in_array($option['id'],$value)) {
                $out .= $join . xarVarPrepForDisplay($option['name']);
                $join = ' | ';
            }
        }
        return $out;
    }

}


?>
