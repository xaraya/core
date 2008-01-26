<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textbox');
/**
 * Handle E-mail property
 * @author mikespub <mikespub@xaraya.com>
*/
class EmailProperty extends TextBoxProperty
{
    public $id         = 26;
    public $name       = 'email';
    public $desc       = 'E-Mail';
    public $reqmodules = array('roles');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'email';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            // cfr. pnVarValidate in pnLegacy.php
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui';
            if (preg_match($regexp,$value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('#(1) #(2): the format is incorrect', $this->name, $this->desc);
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
