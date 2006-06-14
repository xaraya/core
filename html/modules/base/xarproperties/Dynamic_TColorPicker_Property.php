<?php
/**
 * Dynamic Color Picker property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
class Dynamic_TColorPicker_Property extends Dynamic_Property
{
    public $size      = 10;
    public $maxlength = 7;
    public $min       = 7;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'tcolorpicker';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 44;
        $info->name = 'tcolorpicker';
        $info->desc = 'Tigra Color Picker';

        return $info;
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

    function showInput($data = array())
    {
        if (empty($maxlength) && isset($this->max)) {
            $this->maxlength = $this->max;
            if ($this->size > $this->maxlength) {
                $this->size = $this->maxlength;
            }
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
        $data['size']     = $this->size;
        $data['maxlength']= $this->maxlength;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);

        return parent::showInput($data);
    }

}
?>
