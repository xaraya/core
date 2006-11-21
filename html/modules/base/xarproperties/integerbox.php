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

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        $this->size      = 10;
        $this->maxlength = 30;
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($value) || $value === '') {
            if (isset($this->min)) {
                $this->value = $this->min;
            } elseif (isset($this->max)) {
                $this->value = $this->max;
            } else {
                $this->value = 0;
            }
        } elseif (is_numeric($value)) {
            $value = intval($value);
            if (isset($this->min) && isset($this->max) && ($this->min > $value || $this->max < $value)) {
                $this->invalid = xarML('integer : allowed range is between #(1) and #(2)',$this->min,$this->max);
                $this->value = null;
                return false;
            } elseif (isset($this->min) && $this->min > $value) {
                $this->invalid = xarML('integer : must be #(1) or more',$this->min);
                $this->value = null;
                return false;
            } elseif (isset($this->max) && $this->max < $value) {
                $this->invalid = xarML('integer : must be #(1) or less',$this->max);
                $this->value = null;
                return false;
            }
            $this->value = $value;
        } else {
            $this->invalid = xarML('integer');
            $this->value = null;
            return false;
        }
        return true;
    }

    // Trick: use the parent method with a different template :-)
    public function showValidation(Array $args = array())
    {
        // allow template override by child classes
        if (!isset($args['template'])) {
            // can't use this yet, need to decide on a name
            //$args['template'] = $this->getTemplate();
            $args['template'] = 'numberbox';
        }

        return parent::showValidation($args);
    }
}
?>
