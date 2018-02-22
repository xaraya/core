<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * This property displays a dropdown containing a list of integers
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
	/**
 * Validate the value of a input
 *  
 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
 */

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
            $this->value = intval($value);
        } else {
            $this->invalid = xarML('integer: #(1)', $this->name);
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        }
        if (count($this->options) == 0 && (isset($this->min) || isset($this->max)) ) {
            if ( (isset($this->min) && $this->value < $this->min) ||
                 (isset($this->max) && $this->value > $this->max) ) {
                $this->invalid = xarML('integer in range');
                xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
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
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        } else {
            $this->invalid = xarML('integer selection');
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        }
    }
}

?>