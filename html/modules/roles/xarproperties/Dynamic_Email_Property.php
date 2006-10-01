<?php
/**
 * Handle E-mail property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/*
 * Handle E-mail property
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');

class Dynamic_Email_Property extends Dynamic_TextBox_Property
{
    public $id         = 26;
    public $name       = 'email';
    public $desc       = 'E-Mail';
    public $reqmodules = array('roles');

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'email';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }

         if (!empty($value) && strlen($value) > $this->maxlength) {
            $this->invalid = xarML('E-Mail : must be less than #(1) characters long',$this->maxlength + 1);
            $this->value = $value;
            return false;
        } elseif (isset($this->min) && strlen($value) < $this->min) {
            $this->invalid = xarML('E-Mail : must be at least #(1) characters long',$this->min);
            $this->value = $value;
            return false;
        }
        if (!empty($value)) {
            // cfr. pnVarValidate in pnLegacy.php
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui';
            if (preg_match($regexp,$value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('E-Mail');
                $this->value = $value;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }
}

?>
