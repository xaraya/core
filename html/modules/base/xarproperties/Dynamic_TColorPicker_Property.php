<?php
/**
 * File: $Id$
 *
 * Dynamic TColor Picker Property
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
 * Class for dynamic calendar property
 *
 * @package dynamicdata
 */
class Dynamic_TColorPicker_Property extends Dynamic_Property
{
    var $size = 10;
    var $maxlength = 7;
    var $min = 7;

    function validateValue($value = NULL)
    {
        if (!isset($value)) {
            $value = $this->value;
        }

        if (!empty($value)) {
            if (strlen($value) > $this->maxlength || !preg_match('/^\#(([a-f0-9]{3})|([a-f0-9]{6}))$/i', $value)) {
                $this->invalid = xarML('color must be in the format "#RRGGBB" or "#RGB"');
                $this->value = null;
                return false;
            }
        }
        $this->value = $value;
        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (empty($maxlength) && isset($this->max)) {
            $this->maxlength = $this->max;
            if ($this->size > $this->maxlength) {
                $this->size = $this->maxlength;
            }
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        if (!isset($value)) {
            $value = $this->value;
        }

        // Include color picker javascript options.
        // Allows the options to be over-ridden in a theme.
        xarModAPIFunc(
            'base', 'javascript', 'modulecode',
            array('module' => 'base', 'filename' => 'tcolorpickeroptions.js')
        );

        // Include color picker javascript.
        xarModAPIFunc(
            'base','javascript','modulefile',
            array('module' => 'base', 'filename' => 'tcolorpicker.js')
        );
        /*
        // Create the tags.
        $output = '<input type="text" name="'.$name.'" id="'.$id.'_input" value="'.xarVarPrepForDisplay($value).'" size="' . $this->size . '" maxlength="' . $this->maxlength . '" />'
            . '<a href="javascript:TCP.popup(document.getElementById(\''.$id.'_input\'), 1)">'
            . '<img src="' . xarTplGetImage('tcolorpicker.gif', 'base') . '" width="15" height="13" border="0" alt="' . xarML('Click Here to select a color') . '" />'
            . '</a>';

        if (!empty($this->invalid)) {
            $output .= ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>';
        }
        */
        $data['baseuri']   =xarServerGetBaseURI();
        $data['name']     = $name;
        $data['id']       = $id;
        $data['size']     = $this->size;
        $data['maxlength']= $this->maxlength;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        return xarTplProperty('base', 'tcolorpicker', 'showinput', $data);
    }

    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        if (isset($value)) {
            $data['value'] = xarVarPrepHTMLDisplay($value);
        } else {
            $data['value'] = xarVarPrepHTMLDisplay($this->value);
        }

         return xarTplProperty('base', 'tcolorpicker', 'showoutput', $data);
    }



    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 44,
                              'name'       => 'tcolorpicker',
                              'label'      => 'Tigra Color Picker',
                              'format'     => '44',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>
