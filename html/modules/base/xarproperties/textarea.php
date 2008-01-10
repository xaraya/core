<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Handle text area property
 */
class TextAreaProperty extends DataProperty
{
    public $id         = 3;
    public $name       = 'textarea';
    public $desc       = 'Small Text Area';
    public $reqmodules = array('base');

    public $display_rows = 2;
    public $display_columns = 35;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        $this->tplmodule = 'base';
        $this->template = 'textarea';
        $this->filepath   = 'modules/base/xarproperties';

        $args = array();
        try {
            $args = unserialize($args);
        } catch (Exception $e) {}
        if(!empty($args['rows'])) $this->display_rows = $args['rows'];
        if(!empty($args['cols'])) $this->display_columns = $args['cols'];

        // check validation for allowed rows/cols (or values)
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

    function aliases()
    {
        $a1['id']   = 4;
        $a1['name'] = 'textarea_medium';
        $a1['desc'] = 'Medium Text Area';
        $a1['args'] = array('rows' => 8);
        $a1['reqmodules'] = array('base');

        $a2['id']   = 5;
        $a2['name'] = 'textarea_large';
        $a2['desc'] = 'Large Text Area';
        $a2['args'] = array('rows' => 20);
        $a2['reqmodules'] = array('base');

        return array($a1, $a2);
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        // TODO: allowable HTML ?
        $this->value = $value;
        return true;
    }

    public function showInput(Array $data = array())
    {
        // TODO: the way the template is organized now, this only works when an id is set.
        $data['value'] = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->value);
        if(empty($data['rows'])) $data['rows'] = $this->display_rows;
        if(empty($data['cols'])) $data['cols'] = $this->display_columns;

        return parent::showInput($data);
    }

    // check validation for allowed rows/cols (or values)
    /*public function parseValidation($validation = '')
    {
        if (is_string($validation) && strchr($validation,':')) {
            list($rows,$cols) = explode(':',$validation);
            if ($rows !== '' && is_numeric($rows)) {
                $this->display_rows = $rows;
            }
            if ($cols !== '' && is_numeric($cols)) {
                $this->display_columns = $cols;
            }
        }
    }
    */

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
    /*public function showValidation(Array $args = array())
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
    */

    /**
     * Update the current validation rule in a specific way for each property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] new validation rule
     * @param $args['id'] id of the field
     * @returns bool
     * @return bool true if the validation rule could be processed, false otherwise
     */
/*    public function updateValidation(Array $args = array())
     {
         extract($args);

         // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;

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
     */

}

?>
