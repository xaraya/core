<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
            if (is_array($value) && trim($value[0]) == trim($value[1])) {
                $value = $value[0];
            } else {
                if (!empty($this->validation_email_confirm_invalid)) {
                    $this->invalid = xarML($this->validation_email_confirm_invalid);
                } else {
                    $this->invalid = xarML('Emails did not match');
                }
                xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
                return false;
            }
        }

        $value = trim($value);
        if (!parent::validateValue($value)) return false;
        if (!empty($value)) {
            sys::import('xaraya.validations');
            $boolean = ValueValidations::get('email');
            try {
                $boolean->validate($value, array());
            } catch (Exception $e) {
                if (!empty($this->validation_email_invalid)) {
                    $this->invalid = xarML($this->validation_email_invalid);
                } else {
                    $this->invalid = xarML('The email format is incorrect');
                }
                xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
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