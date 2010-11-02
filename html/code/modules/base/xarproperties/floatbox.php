<?php
/**
 * @package modules
 * @subpackage base module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.integerbox');

/**
 * Handle floatbox property
 */
class FloatBoxProperty extends NumberBoxProperty
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
        if (!is_numeric($this->value) && !empty($this->value)) throw new Exception(xarML('The default value of a #(1) must be numeric',$this->name));
    }

    public function castType($value=null)
    {
        if (!is_null($value)) return (float)$value;
        return 0;
    }
}

?>