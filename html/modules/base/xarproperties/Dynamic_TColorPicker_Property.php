<?php
/**
 * Dynamic Color Picker property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * @author mikespub <mikespub@xaraya.com>
*/
class Dynamic_TColorPicker_Property extends Dynamic_Property
{
    public $size      = 10;
    public $maxlength = 7;
    public $min       = 7;
    
    function __construct($args) {
        parent::__construct($args);
        
        $this->requiresmodule = 'base';
        
        $this->id        = 44;
        $this->name      = 'tcolorpicker';
        $this->tplmodule = 'base';
        $this->label     = 'Tigra Color Picker';
        $this->format    = '44';
    }

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

        $data['baseuri']  = xarServerGetBaseURI();
        $data['name']     = $name;
        $data['id']       = $id;
        $data['size']     = $this->size;
        $data['maxlength']= $this->maxlength;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showinput', $data);
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

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showoutput', $data);
    }
}
?>
