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

}

?>
