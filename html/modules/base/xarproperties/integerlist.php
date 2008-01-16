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
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the numberlist property
 */
class NumberListProperty extends SelectProperty
{
    public $id         = 16;
    public $name       = 'integerlist';
    public $desc       = 'Number List';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // check configuration for allowed min/max values
        if (count($this->options) == 0 && !empty($this->configuration) && strchr($this->configuration,':')) {
            list($min,$max) = explode(':',$this->configuration);
            if ($min !== '' && is_numeric($min)) {
                $this->min = intval($min);
            }
            if ($max !== '' && is_numeric($max)) {
                $this->max = intval($max);
            }
            if (isset($this->min) && isset($this->max)) {
                for ($i = $this->min; $i <= $this->max; $i++) {
                    $this->options[] = array('id' => $i, 'name' => $i);
                }
            } else {
                // you're in trouble :)
            }
        }
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
            $this->value = intval($value);
        } else {
            $this->invalid = xarML('integer: #(1)', $this->name);
            $this->value = null;
            return false;
        }
        if (count($this->options) == 0 && (isset($this->min) || isset($this->max)) ) {
            if ( (isset($this->min) && $this->value < $this->min) ||
                 (isset($this->max) && $this->value > $this->max) ) {
                $this->invalid = xarML('integer in range');
                $this->value = null;
                return false;
            }
        } elseif (count($this->options) > 0) {
            foreach ($this->options as $option) {
                if ($option['id'] == $this->value) {
                    return true;
                }
            }
            $this->invalid = xarML('integer in selection');
            $this->value = null;
            return false;
        } else {
            $this->invalid = xarML('integer selection');
            $this->value = null;
            return false;
        }
    }
}

?>
