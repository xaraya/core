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
 * Handle floatbox property
 */
class FloatBoxProperty extends TextBoxProperty
{
    public $id         = 17;
    public $name       = 'floatbox';
    public $desc       = 'Number Box (float)';

    public $display_size                    = 10;
    public $display_maxlength               = 30;

    public $defaultvalue   = 0;
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        if ($this->value == '') $this->value = $this->defaultvalue;
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!isset($value) || $value === '') {
            if (isset($this->min)) {
                $this->value = $this->min;
            } elseif (isset($this->max)) {
                $this->value = $this->max;
            } else {
                $this->value = 0;
            }
        } elseif (is_numeric($value)) {
            $this->value = (float) $value;
            if (isset($this->min) && isset($this->max) && ($this->min > $value || $this->max < $value)) {
                $this->invalid = xarML('float : allowed range is between #(1) and #(2)',$this->min,$this->max);
                $this->value = null;
                return false;
            } elseif (isset($this->min) && $this->min > $value) {
                $this->invalid = xarML('float : must be #(1) or more',$this->min);
                $this->value = null;
                return false;
            } elseif (isset($this->max) && $this->max < $value) {
                $this->invalid = xarML('float : must be #(1) or less',$this->max);
                $this->value = null;
                return false;
            }
        } else {
            $this->invalid = xarML('float: #(1)', $this->name);
            $this->value = null;
            return false;
        }
        return true;
    }
}

?>
