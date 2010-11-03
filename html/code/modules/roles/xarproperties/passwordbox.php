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

sys::import('modules.base.xarproperties.textbox');
/**
 * Handle Passwordbox property
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

    function aliases()
    {
        $a1['id']   = 461;
        $a1['name'] = 'password';
        $a1['desc'] = 'Password Text Box';
        $a1['reqmodules'] = array('roles');
        return array($a1);
    }

    function setValue($value=null)
    {
         $this->value = $this->encrypt($value);
    }

    public function validateValue($value = null)
    {
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

    public function showInput(Array $data = array())
    {
        if (isset($data['confirm'])) $this->validation_password_confirm = $data['confirm'];
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        //we don't want to show the password, but leave open the possibility of displaying some value here
        if (!isset($data['value'])) $data['value'] = ' ';
        return parent::showOutput($data);
    }


}
?>
