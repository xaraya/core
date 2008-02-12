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
sys::import('modules.base.xarproperties.textbox');
/**
 * Handle the numberbox property
 */
class NumberBoxProperty extends TextBoxProperty
{
    public $id         = 15;
    public $name       = 'integerbox';
    public $desc       = 'Number Box';

    public $validation_min_value           = null;
    public $validation_min_value_invalid;
    public $validation_max_value           = null;
    public $validation_max_value_invalid;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        $this->display_size      = 10;
        $this->display_maxlength = 30;
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!isset($value) || $value === '') {
            if (isset($this->validation_min_value)) {
                $this->setValue($this->validation_min_value);
            } elseif (isset($this->validation_max_value)) {
                $this->setValue($this->validation_max_value);
            } else {
                $this->setValue();
            }
        } elseif (is_numeric($value)) {
            $value = intval($value);
            if (isset($this->min) && isset($this->validation_max_value) && ($this->validation_min_value > $value || $this->validation_max_value < $value)) {
                $this->invalid = xarML('integer : allowed range is between #(1) and #(2)',$this->validation_min_value,$this->validation_max_value);
                $this->setValue();
                return false;
            } elseif (isset($this->min) && $this->validation_min_value > $value) {
                if (!empty($this->validation_min_value_invalid)) {
                    $this->invalid = xarML($this->validation_min_value_invalid);
                } else {
                    $this->invalid = xarML('integer : must be #(1) or more',$this->validation_min_value);
                }
                $this->setValue();
                return false;
            } elseif (isset($this->validation_max_value) && $this->validation_max_value < $value) {
                if (!empty($this->validation_max_value_invalid)) {
                    $this->invalid = xarML($this->validation_max_value_invalid);
                } else {
                    $this->invalid = xarML('integer : must be #(1) or less',$this->validation_max_value);
                }
                $this->setValue();
                return false;
            }
        } else {
            $this->invalid = xarML('integer: #(1)', $this->name);
            $this->setValue();
            return false;
        }
        return true;
    }
}
?>
