<?php
/**
 * Dynamic Calendar Property
 *
 * @package dynamicdata
 * @subpackage properties
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

    function validateValue($value = null)
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

        // Create the tags.
        $output = '<input type="text" name="'.$name.'" id="'.$id.'_input" value="'.xarVarPrepForDisplay($value).'" size="' . $this->size . '" maxlength="' . $this->maxlength . '" />'
            . '<a href="javascript:TCP.popup(document.getElementById(\''.$id.'_input\'), 1)">'
            . '<img src="' . xarTplGetImage('tcolorpicker.gif', 'base') . '" width="15" height="13" border="0" alt="' . xarML('Click Here to select a color') . '" />'
            . '</a>';

        if (!empty($this->invalid)) {
            $output .= ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>';
        }
        return $output;
    }

    function showOutput($args = array())
    {
        extract($args);
        if (isset($value)) {
            return xarVarPrepHTMLDisplay($value);
        } else {
            return xarVarPrepHTMLDisplay($this->value);
        }
    }

}

?>