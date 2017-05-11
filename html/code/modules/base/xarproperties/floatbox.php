<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.integerbox');
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
 * This property displays a textbox whose content is a number of type float
 */
class FloatBoxProperty extends NumberBoxProperty
{
    public $id         = 17;
    public $name       = 'floatbox';
    public $desc       = 'Number Box (float)';

    public $display_size                   = 10;
    public $display_maxlength              = 30;
    public $display_numberformat           = '2';

    public $basetype   = 'decimal';
    public $defaultvalue   = 0;
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        if ($this->value == '') $this->value = $this->defaultvalue;
        if (!is_numeric($this->value) && !empty($this->value)) throw new Exception(xarML('The default value of a #(1) must be numeric',$this->name));
    }
/**
 * Convert an integer or string value to true/false
 * 
 * @param  mixed value The value to be converted
 * @return bool  Returns true if the integer or string value is 1, "1" or "true"; otherwise returns false.
 */
    public function castType($value=null)
    {
        if (!is_null($value)) return (float)$value;
        return 0;
    }
}

?>
