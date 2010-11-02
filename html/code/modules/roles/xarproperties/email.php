<?php
/**
 * @package modules
 * @subpackage roles module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * 
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

    public $validation_email_invalid;
    public $validation_email_confirm     = 0;
    public $validation_email_confirm_invalid;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'email';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) $value = "";

        if ($this->validation_email_confirm) {
            if (is_array($value) && $value[0] == $value[1]) {
                $value = $value[0];
            } else {
                if (!empty($this->validation_email_confirm_invalid)) {
                    $this->invalid = xarML($this->validation_email_confirm_invalid);
                } else {
                    $this->invalid = xarML('Emails did not match');
                }
                return false;
            }
        }

        if (!parent::validateValue($value)) return false;
        if (!empty($value)) {
            // cfr. pnVarValidate in pnLegacy.php
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui';
            if (!preg_match($regexp,$value)) {
                if (!empty($this->validation_email_invalid)) {
                    $this->invalid = xarML($this->validation_email_invalid);
                } else {
                    $this->invalid = xarML('The email format is incorrect');
                }
                $this->value = $value;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['confirm'])) $this->validation_email_confirm = $data['confirm'];
        return parent::showInput($data);
    }

}

?>