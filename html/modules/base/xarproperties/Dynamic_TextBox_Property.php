<?php
/**
 * File: $Id$
 *
 * Dynamic Text Box Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
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
    var $size = 50;
    var $maxlength = 254;

    var $min = null;
    var $max = null;

    function Dynamic_TextBox_Property($args)
    {
        $this->Dynamic_Property($args);

        // check validation for allowed min/max length (or values)
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
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
        } else {
    // TODO: allowable HTML ?
            $this->value = $value;
            return true;
        }
    }

//    function showInput($name = '', $value = null, $size = 0, $maxlength = 0, $id = '', $tabindex = '')
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
/*        return '<input type="text"'.
               ' name="' . $name . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/
            $data['name']     = $name;
            $data['id']       = $id;
            $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
            $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
            $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
            $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
            $data['size']     = !empty($size) ? $size : $this->size;

      $template="";
      return xarTplProperty('base', 'textbox', 'showinput', $data);
    }

    function showOutput($args = array())
    {
        extract($args);

        if (isset($value)) {
            $value=xarVarPrepHTMLDisplay($value);
        } else {
            $value=xarVarPrepHTMLDisplay($this->value);
        }
        $data=array();

        $data['value'] = $value;

        $template="";
        return xarTplProperty('base', 'textbox', 'showoutput', $data);

    }

    // check validation for allowed min/max length (or values)
    function parseValidation($validation = '')
    {
        if (is_string($validation) && strchr($validation,':')) {
            list($min,$max) = explode(':',$validation);
            if ($min !== '' && is_numeric($min)) {
                $this->min = $min; // could be int or float - cfr. FloatBox below
            }
            if ($max !== '' && is_numeric($max)) {
                $this->max = $max; // could be int or float - cfr. FloatBox below
            }
        }
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
                              'id'         => 2,
                              'name'       => 'textbox',
                              'label'      => 'Text Box',
                              'format'     => '2',
                              'validation' => '',
                              'source'     => '',
                              'dependancies' => '',
                              'requiresmodule' => '',
                              'aliases' => '',
                              'args'       => serialize( $args ),
                            // ...
                           );
        return $baseInfo;
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
        $data['other'] = '';
        // if we didn't match the above format
        if (!isset($this->min) && !isset($this->max)) {
            $data['other'] = xarVarPrepForDisplay($this->validation);
        }

        // allow template override by child classes
        if (!isset($template)) {
            $template = '';
        }
        return xarTplProperty('base', 'textbox', 'validation', $data);
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
                 // we have some minimum and/or maximum length
                 if ($min !== '' || $max !== '') {
                     $this->validation = $min .':'. $max;

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
