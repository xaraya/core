<?php
/* Include the base class */
sys::import('modules.base.xarproperties.textbox');

/**
 * The PasswordBox property displays is a wrapper for a HTML input of type password
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
class PassBoxProperty extends TextBoxProperty
{
    public $id         = 46;
    public $name       = 'passwordbox';
    public $desc       = 'Password';
    public $reqmodules = array('roles');
    public $aliases    = array('id' => 461);

    public $password = null;

    public $display_size                    = 25;
    public $validation_min_length           = 4;
    public $validation_max_length           = 30;
    public $validation_password_confirm     = 0;
    public $validation_password_confirm_invalid;
    public $initialization_hash_type        = 'md5';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template ='password';
        $this->filepath   = 'modules/roles/xarproperties';
    }

	/**
	 * @return array   array of provided elements
	 */
    function aliases()
    {
        if (get_class($this) !== 'PassBoxProperty') {
            return array();
	}

        $a1['id']   = 461;
        $a1['name'] = 'password';
        $a1['desc'] = 'Password Text Box';
        $a1['reqmodules'] = array('roles');
        return array($a1);
    }

	/**
	 * Set the value of input
	 * 
	 * @param  string value The value of the input
	 * @return string    return a encrypted value
	 */	
    function setValue($value=null)
    {
        $this->value = $this->encrypt($value);
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

        if ($this->validation_password_confirm) {
            if (is_array($value) && $value[0] == $value[1]) {
                $value = $value[0];
            } else {
                if (!empty($this->validation_password_confirm_invalid)) {
                    $this->invalid = xarML($this->validation_password_confirm_invalid);
                } else {
                    $this->invalid = xarML('Passwords did not match');
                }
                xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
                $this->value = null;
                return false;
            }
        }

        if (!(empty($value) && !empty($this->value))) {
            if (!parent::validateValue($value)) return false;

            $this->password = $value;
            $this->setValue($value);
        }

        return true;
    }

	/**
	 * Encrypt the provided value
	 *
	 * @param string value  The value to be encrypted
	 * @return string  Returns the hashed value 
	 * @throws Exception Thrown if hash type not defined
	 */
    public function encrypt($value = null)
    {
        if (empty($value)) return null;
        
        // If we removed the default, revert to md5
        if (empty($this->initialization_hash_type)) return md5($value);
        // Do not encrypt only if explicitly stated
        if ($this->initialization_hash_type == 'none') return $value;
        try {
            return hash($this->initialization_hash_type, $value);
        } catch (Exception $e) {
        // Bad hash type? Go back to md5
            return md5($value);
        }
    }

	/**
	 * Display a textbox for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if (isset($data['confirm'])) $this->validation_password_confirm = $data['confirm'];
        return parent::showInput($data);
    }

	/**
	 * Display a textbox for output
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */
    public function showOutput(Array $data = array())
    {
        // We don't want to show the password, but leave open the possibility of displaying some value here
        if (!isset($data['value'])) $data['value'] = ' ';
        return parent::showOutput($data);
    }


}
