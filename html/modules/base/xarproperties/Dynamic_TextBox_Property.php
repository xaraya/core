<?php
/**
 * Dynamic Textbox Property
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
/* Include parent class */
include_once "modules/dynamicdata/class/properties.php";

/**
 * handle the textbox property
 *
 * @package dynamicdata
 */
class Dynamic_TextBox_Property extends Dynamic_Property
{
    public $size      = 50;
    public $maxlength = 254;

    public $min       = null;
    public $max       = null;
    public $regex     = null;

    function __construct($args)
    {
        parent::__construct($args);

        // Set for runtime
        $this->tplmodule = 'base';
        $this->template = 'textbox';

        // check validation for allowed min/max length (or values)
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

     static function getRegistrationInfo()
     {
         $info = new PropertyRegistration();
         $info->reqmodules = array('base');
         $info->id   = 2;
         $info->name = 'textbox';
         $info->desc = 'Text Box';

         return $info;
     }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        } elseif (is_array($value)) {
            $value = serialize($value);
        }
        if (!empty($value) && strlen($value) > $this->maxlength) {
            $this->invalid = xarML('text : must be less than #(1) characters long',$this->max + 1);
            $this->value = null;
            return false;
        } elseif (isset($this->min) && strlen($value) < $this->min) {
            $this->invalid = xarML('text : must be at least #(1) characters long',$this->min);
            $this->value = null;
            return false;
        } elseif (!empty($this->regex) && !preg_match($this->regex, $value)) {
            $this->invalid = xarML('text : does not match regular expression');
            $this->value = null;
            return false;
        } else {
    // TODO: allowable HTML ?
            $this->value = $value;
            return true;
        }
    }

    function showInput($data = array())
    {
        // Process the parameters
        if (!isset($data['maxlength']) && isset($this->max)) {
            $this->maxlength = $this->max;
            if ($this->size > $this->maxlength) {
                $this->size = $this->maxlength;
            }
        }

        // Prepare for templating
        $data['value']    = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->value);
        if(!isset($data['maxlength'])) $data['maxlength'] = $this->maxlength;
        if(!isset($data['size']))      $data['size']      = $this->size;
        if(!isset($data['onfocus']))   $data['onfocus']   = null;

        // Let parent deal with the rest
        return parent::showInput($data);
    }

    // check validation for allowed min/max length (or values)
    function parseValidation($validation = '')
    {
        if (is_string($validation) && strchr($validation,':')) {
            $fields = explode(':',$validation);
            $min = array_shift($fields);
            $max = array_shift($fields);
            if ($min !== '' && is_numeric($min)) {
                $this->min = $min; // could be int or float - cfr. FloatBox below
            }
            if ($max !== '' && is_numeric($max)) {
                $this->max = $max; // could be int or float - cfr. FloatBox below
            }
            if (count($fields) > 0) {
                $this->regex = join(':', $fields); // the rest belongs to the regular expression
            }
        }
    }

    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->validation = $validation;
            // check validation for allowed min/max length (or values)
            $this->parseValidation($validation);
        }
        $data['min'] = isset($this->min) ? $this->min : '';
        $data['max'] = isset($this->max) ? $this->max : '';
        $data['regex'] = isset($this->regex) ? xarVarPrepForDisplay($this->regex) : '';
        $data['other'] = '';
        // if we didn't match the above format
        if (!isset($this->min) && !isset($this->max) && !isset($this->regex)) {
            $data['other'] = xarVarPrepForDisplay($this->validation);
        }

    // FIXME: this won't work when called by a property from a different module
        // allow template override by child classes (or in BL tags/API calls)
        if (empty($template)) {
            $template = 'textbox';
        }
        return xarTplProperty('base', $template, 'validation', $data);
    }

    /**
     * Update the current validation rule in a specific way for each property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] new validation rule
     * @param $args['id'] id of the field
     * @returns bool
     * @return bool true if the validation rule could be processed, false otherwise
     */
     function updateValidation($args = array())
     {
         extract($args);

         // in case we need to process additional input fields based on the name
         if (empty($name)) {
             $name = 'dd_'.$this->id;
         }

         // do something with the validation and save it in $this->validation
         if (isset($validation)) {
             if (is_array($validation)) {
                 if (isset($validation['min']) && $validation['min'] !== '' && is_numeric($validation['min'])) {
                     $min = $validation['min'];
                 } else {
                     $min = '';
                 }
                 if (isset($validation['max']) && $validation['max'] !== '' && is_numeric($validation['max'])) {
                     $max = $validation['max'];
                 } else {
                     $max = '';
                 }
                 if (!empty($validation['regex']) && is_string($validation['regex'])) {
                     $regex = $validation['regex'];
                 } else {
                     $regex = '';
                 }
                 // we have some minimum and/or maximum length and/or regular expression
                 if ($min !== '' || $max !== '' || $regex !== '') {
                     $this->validation = $min .':'. $max;
                     if (!empty($regex)) {
                         $this->validation .= ':'. $regex;
                     }

                 // we have some other rule
                 } elseif (!empty($validation['other'])) {
                     $this->validation = $validation['other'];

                 } else {
                     $this->validation = '';
                 }
             } else {
                 $this->validation = $validation;
             }
         }

         // tell the calling function that everything is OK
         return true;
     }
}

?>
