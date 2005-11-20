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
/*
 * @author mikespub <mikespub@xaraya.com>
*/
class Dynamic_TextArea_Property extends Dynamic_Property
{
    public $rows = 8;
    public $cols = 35;
 
    function Dynamic_TextArea_Property($args)
    {
         $this->Dynamic_Property($args);

        // check validation for allowed rows/cols (or values)
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
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

//   function showInput($name = '', $value = null, $rows = 8, $cols = 50, $wrap = 'soft', $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
/*        return '<textarea' .
               ' name="' . $name . '"' .
               ' rows="'. (!empty($rows) ? $rows : $this->rows) . '"' .
               ' cols="'. (!empty($cols) ? $cols : $this->cols) . '"' .
               ' wrap="'. (!empty($wrap) ? $wrap : $this->wrap) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               '>' . (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '</textarea>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['rows']     = !empty($rows) ? $rows : $this->rows;
        $data['cols']     = !empty($cols) ? $cols : $this->cols;

        $template="";
        return xarTplProperty('base', 'textarea', 'showinput', $data);

    }

    function showOutput($args = array())
    {
         extract($args);
         $data=array();
         
         if (isset($value)) {
            //return xarVarPrepHTMLDisplay($value);
            $data['value'] = xarVarPrepHTMLDisplay($value);
         } else {
            //return xarVarPrepHTMLDisplay($this->value);
            $data['value'] = xarVarPrepHTMLDisplay($this->value);
         }
         $template="";
         return xarTplProperty('base', 'textarea', 'showoutput', $data);
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
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
    function getBasePropertyInfo()
    {
        $args = array();
        $args['rows'] = 8;
        $aliases[] = array(
                            'id'         => 4,
                            'name'       => 'textarea_medium',
                            'label'      => 'Medium Text Area',
                            'format'     => '4',
                            'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'args' => serialize( $args ),
                            
                            // ...
                           );
     
        $args['rows'] = 20;     
        $aliases[] = array(
                              'id'         => 5,
                              'name'       => 'textarea_large',
                              'label'      => 'Large Text Area',
                              'format'     => '5',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'args' => serialize( $args ),
                            // ...
                           );

        $args['rows'] = 2;
        $baseInfo = array(
                            'id'         => 3,
                            'name'       => 'textarea_small',
                            'label'      => 'Small Text Area',
                            'format'     => '3',
                            'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases' => $aliases,
                            'args' => serialize( $args ),
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
