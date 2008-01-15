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
/* Include parent class */
sys::import('modules.dynamicdata.class.properties');
/**
 * Handle the textbox property
 */
class TextBoxProperty extends DataProperty
{
    public $id         = 2;
    public $name       = 'textbox';
    public $desc       = 'Text Box';
    public $reqmodules = array('base');

    public $display_size                    = 50;
    public $display_maxlength               = 254;
    public $validation_min_length           = null;
    public $validation_max_length           = null;
    public $validation_regex                = null;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime
        $this->tplmodule = 'base';
        $this->template = 'textbox';
        $this->filepath   = 'modules/base/xarproperties';

        // check validation for allowed min/max length (or values)
        if (!empty($this->configuration)) {
            $this->parseConfiguration($this->configuration);
        }
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!isset($value)) {
            $value = $this->value;
        } elseif (is_array($value)) {
            $value = serialize($value);
        }

        if (isset($this->validation_max_length)  && strlen($value) > $this->display_maxlength) {
            $this->invalid = xarML('#(1) #(3): must be less than #(2) characters long', $this->name,$this->validation_max_length + 1, $this->desc);
            $this->value = null;
            return false;
        } elseif (isset($this->validation_min_length) && strlen($value) < $this->validation_min_length) {
            $this->invalid = xarML('#(1) #(3): must be at least #(2) characters long', $this->name,$this->validation_min_length, $this->desc);
            $this->value = null;
            return false;
        } elseif (!empty($this->validation_regex) && !preg_match($this->validation_regex, $value)) {
            $this->invalid = xarML('#(1) #(2): does not match required pattern', $this->name, $this->desc);
            $this->value = null;
            return false;
        } else {
    // TODO: allowable HTML ?
            $this->value = $value;
            return true;
        }
    }

    public function showInput(Array $data = array())
    {
        // Process the parameters
        if (!isset($data['maxlength']) && isset($this->validation_max_length)) {
            $this->display_maxlength = $this->validation_max_length;
            if ($this->display_size > $this->display_maxlength) {
                $this->display_size = $this->display_maxlength;
            }
        }

        // Prepare for templating
        $data['value']    = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->value);
//        if(!isset($data['maxlength'])) $data['maxlength'] = $this->display_maxlength;
//        if(!isset($data['size']))      $data['size']      = $this->display_size;
        if(!isset($data['onfocus']))   $data['onfocus']   = null;

        // Let parent deal with the rest
        return parent::showInput($data);
    }

    // check validation for allowed min/max length (or values)
/*    public function parseConfiguration($validation = '')
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
*/
    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
/*    public function showConfiguration(Array $data = array())
    {
        extract($data);

        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->configuration = $validation;
            // check validation for allowed min/max length (or values)
            $this->parseConfiguration($validation);
        }
        $data['min'] = isset($this->min) ? $this->min : '';
        $data['max'] = isset($this->max) ? $this->max : '';
        $data['regex'] = isset($this->regex) ? xarVarPrepForDisplay($this->regex) : '';
        $data['other'] = '';
        // if we didn't match the above format
        if (!isset($this->min) && !isset($this->max) && !isset($this->regex)) {
            $data['other'] = xarVarPrepForDisplay($this->configuration);
        }

    // FIXME: this won't work when called by a property from a different module
        // allow template override by child classes (or in BL tags/API calls)
        if (empty($template)) {
            $template = 'textbox';
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
/*    public function updateConfiguration(Array $args = array())
     {
         extract($args);

         // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;

         // do something with the validation and save it in $this->configuration
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
                     $this->configuration = $min .':'. $max;
                     if (!empty($regex)) {
                         $this->configuration .= ':'. $regex;
                     }

                 // we have some other rule
                 } elseif (!empty($validation['other'])) {
                     $this->configuration = $validation['other'];

                 } else {
                     $this->configuration = '';
                 }
             } else {
                 $this->configuration = $validation;
             }
         }

         // tell the calling function that everything is OK
         return true;
     }
*/}

?>
