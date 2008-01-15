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
/* include the parent class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the combo property
 */
class ComboProperty extends SelectProperty
{
    public $id         = 506;
    public $name       = 'combobox';
    public $desc       = 'Combo Dropdown Box';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'combobox';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;

        // First check for text in the text box
        $tbname  = $name.'_tb';
        if (!xarVarFetch($tbname, 'isset', $tbvalue,  NULL, XARVAR_DONT_SET)) {return;}

        if( isset($tbvalue) && ($tbvalue != '') )
        {
            $this->fieldname = $tbname;
            $value = $tbvalue;
        } else {
            // Default to checking the selection box.

            // store the fieldname for configurations who need them (e.g. file uploads)
            $this->fieldname = $name;
            if (!isset($value))
            {
                if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
            }
        }
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        if (!isset($value))
        {
            $value = $this->value;
        }
        $this->value = $value;

        return true;
    }
}
?>
