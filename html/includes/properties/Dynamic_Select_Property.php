<?php
/**
 * File: $Id$
 *
 * Dynamic Data Select Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Handle the select property
 *
 * @package dynamicdata
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

            // or if it contains a ; or a , we'll assume that this is a list of name1;name2;name3 or id1,name1;id2,name2;id3,name3
            } elseif (strchr($this->validation,';') || strchr($this->validation,',')) {
                // allow escaping \; for values that need a semi-colon
                $options = preg_split('/(?<!\\\);/', $this->validation);
                foreach ($options as $option) {
                    $option = strtr($option,array('\;' => ';'));
                    // allow escaping \, for values that need a comma
                    if (preg_match('/(?<!\\\),/', $option)) {
                        // if the option contains a , we'll assume it's an id,name combination
                        list($id,$name) = preg_split('/(?<!\\\),/', $option);
                        $id = strtr($id,array('\,' => ','));
                        $name = strtr($name,array('\,' => ','));
                        array_push($this->options, array('id' => $id, 'name' => $name));
                    } else {
                        // otherwise we'll use the option for both id and name
                        $option = strtr($option,array('\,' => ','));
                        array_push($this->options, array('id' => $option, 'name' => $option));
                    }
                }

            // otherwise we'll leave it alone, for use in any subclasses (e.g. min:max in NumberList, or basedir for ImageList, or ...)
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
        $data=array();

        if (!isset($value)) {
            $data['value'] = $this->value;
        } else {
            $data['value'] = $value;
        }
        if (!isset($options) || count($options) == 0) {
            $data['options'] = $this->options;
        }
        if (empty($name)) {
            $data['name'] = 'dd_' . $this->id;
        } else {
        $data['name'] = $name;
        }
        if (empty($id)) {
            $data['id'] = $data['name'];
        } else {
        $data['id']= $id;
        }
        /*$out = '<select' .
               ' name="' . $name . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               '>';

        foreach ($options as $option) {
            $out .= '<option';
            if (empty($option['id']) || $option['id'] != $option['name']) {
                $out .= ' value="'.$option['id'].'"';
            }
            if ($option['id'] == $value) {
                $out .= ' selected="selected">'.$option['name'].'</option>';
            } else {
                $out .= '>'.$option['name'].'</option>';
            }
        }
        */

        /*$out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        */

        $data['tabindex'] =!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '';
        $data['invalid']  =!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '';

        $template="dropdown";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data ,$template);
        //return $out;
    }

    function showOutput($args = array())
    {
         extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        //$out = '';
        $data=array();
        // TODO: support multiple selection
        $join = '';
        foreach ($this->options as $option) {
            if ($option['id'] == $value) {
                $data['option']['name']=xarVarPrepForDisplay($option['name']);
                //$out .= $join . xarVarPrepForDisplay($option['name']);
                $join = ' | ';
            }
        }

        $template="dropdown";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
        // return $out;
    }

}


?>
