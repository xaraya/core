<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
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
    public $validation_password_confirm = 0;
    public $validation_password_confirm_invalid;

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
        return md5($value);
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['confirm'])) $this->validation_password_confirm = $data['confirm'];
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        //we don't really want to show the password, do we?
        $data['value'] = ' ';
        return parent::showOutput($data);
    }


}
?>