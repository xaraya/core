<?php
/* Include the base class */
sys::import('modules.base.xarproperties.textbox');

/**
 * The Email property manages an email address
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
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

	/**
	 * Validate the value of a textbox
	 *
	 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
	 */
    public function validateValue($value = null)
    {
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name, xarLog::LEVEL_DEBUG);

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
                xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
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
                xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
                $this->value = $value;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

	/**
	 * Display a textbox for input
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if (isset($data['confirm'])) $this->validation_email_confirm = $data['confirm'];
        return parent::showInput($data);
    }

}
