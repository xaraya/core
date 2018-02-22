<?php
/* Include parent class */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * The Textbox property models an HTML input of type text
 * 
 * @package modules\base
 * subpackage base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
 
 /**
  * This property displays a textbox
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
    public $validation_min_length_invalid;
    public $validation_max_length           = null;
    public $validation_max_length_invalid;
    public $validation_regex                = null;
    public $validation_regex_invalid;
    public $initialization_sanitize         = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // Set for runtime
        $this->tplmodule = 'base';
        $this->template = 'textbox';
        $this->filepath   = 'modules/base/xarproperties';
    }

/**
 * Validate the value of a textbox according to the rules inherited and set out in its configuration
 * 
 */
    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (is_array($value)) {
            $value = serialize($value);
        }

        // Remove any unwanted characters
        if ($this->initialization_sanitize)
            $value = filter_var($value, FILTER_SANITIZE_STRING);
        
        if (isset($this->validation_max_length)  && strlen($value) > $this->display_maxlength) {
            if (!empty($this->validation_max_length_invalid)) {
                $this->invalid = xarML($this->validation_max_length_invalid);
            } else {
                $this->invalid = xarML('#(1) field #(3): must be less than #(2) characters long', $this->name,$this->validation_max_length + 1, $this->desc);
            }
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        } elseif (isset($this->validation_min_length) && strlen($value) < $this->validation_min_length) {
            if (!empty($this->validation_min_length_invalid)) {
                $this->invalid = xarML($this->validation_min_length_invalid);
            } else {
                $this->invalid = xarML('#(1) field #(3): must be at least #(2) characters long', $this->name,$this->validation_min_length, $this->desc);
            }
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        } elseif (!empty($this->validation_regex) && !preg_match($this->validation_regex, $value)) {
            if (!empty($this->validation_regex_invalid)) {
                $this->invalid = xarML($this->validation_regex_invalid);
            } else {
                $this->invalid = xarML('#(1) field #(2): does not match required pattern', $this->name, $this->desc);
            }
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        } else {
    // TODO: allowable HTML ?
            $this->setValue($value);
            return true;
        }
    }

/**
 * Display a textbox for input
 * 
 */
    public function showInput(Array $data = array())
    {
        // Should we be doing this? (random)
        if(isset($data['maxlength'])) $this->display_maxlength = $data['maxlength'];
        if(isset($data['size']))      $this->display_size = $data['size'];
        if ($this->display_size > $this->display_maxlength) {
            $this->display_size = $this->display_maxlength;
        }

        // Prepare for templating
        $data['value']    = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->getValue());
        if(!isset($data['onfocus']))   $data['onfocus']   = null;

        return parent::showInput($data);
    }
}

?>