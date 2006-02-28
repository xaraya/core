<?php
/**
 * Dynamic Textarea Property
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
class Dynamic_TextArea_Property extends Dynamic_Property
{
    public $rows = 8;
    public $cols = 35;

    function __construct($args)
    {
        parent::__construct($args);

        $this->tplmodule = 'base';
        $this->template = 'textarea';

        if(isset($args['rows'])) $this->rows = $args['rows'];
        if(isset($args['cols'])) $this->cols = $args['cols'];

        // check validation for allowed rows/cols (or values)
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

    static function getRegistrationInfo()
    {
        // Aliases
        $a1 = new PropertyRegistration();
        $a1->id   = 4;
        $a1->name = 'textarea_medium';
        $a1->desc = 'Medium Text Area';
        $a1->args = array('rows' => 8);
        
        $a2 = new PropertyRegistration();
        $a2->id   = 5;
        $a2->name = 'textarea_large';
        $a2->desc = 'Medium Text Area';
        $a2->args = array('rows' => 20);

        // Composite property registration.
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id      = 3;
        $info->name    = 'textarea_small';
        $info->desc    = 'Small Text Area';
        $info->args    = array('rows' => 2);
        $info->aliases = array($a1, $a2);

        return $info;
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        // TODO: allowable HTML ?
        $this->value = $value;
        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        // TODO: the way the template is organized now, this only works when an id is set.
        $data['rows']     = !empty($rows) ? $rows : $this->rows;
        $data['cols']     = !empty($cols) ? $cols : $this->cols;

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

    // check validation for allowed rows/cols (or values)
    function parseValidation($validation = '')
    {
        if (is_string($validation) && strchr($validation,':')) {
            list($rows,$cols) = explode(':',$validation);
            if ($rows !== '' && is_numeric($rows)) {
                $this->rows = $rows;
            }
            if ($cols !== '' && is_numeric($cols)) {
                $this->cols = $cols;
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

        // get the original values first
        $data['defaultrows'] = $this->rows;
        $data['defaultcols'] = $this->cols;

        if (isset($validation)) {
            $this->validation = $validation;
            // check validation for allowed rows/cols (or values)
            $this->parseValidation($validation);
        }
        $data['rows'] = ($this->rows != $data['defaultrows']) ? $this->rows : '';
        $data['cols'] = ($this->cols != $data['defaultcols']) ? $this->cols : '';
        $data['other'] = '';
        // if we didn't match the above format
        if ($data['rows'] === '' &&  $data['cols'] === '') {
            $data['other'] = xarVarPrepForDisplay($this->validation);
        }

        // allow template override by child classes
        if (empty($template)) {
            $template = 'textarea';
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
                 if (isset($validation['rows']) && $validation['rows'] !== '' && is_numeric($validation['rows'])) {
                     $rows = $validation['rows'];
                 } else {
                     $rows = '';
                 }
                 if (isset($validation['cols']) && $validation['cols'] !== '' && is_numeric($validation['cols'])) {
                     $cols = $validation['cols'];
                 } else {
                     $cols = '';
                 }
                 // we have some rows and/or columns
                 if ($rows !== '' || $cols !== '') {
                     $this->validation = $rows .':'. $cols;

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
