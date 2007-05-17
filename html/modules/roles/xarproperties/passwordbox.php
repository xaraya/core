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

    public $size = 25;
    public $maxlength = 254;

    public $min = 5;
    public $max = null;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template ='password';
        $this->filepath   = 'modules/roles/xarproperties';

        // check validation for allowed min/max length (or values)
        if (!empty($this->validation) && strchr($this->validation,':')) {
            list($min,$max) = explode(':',$this->validation);
            if ($min !== '' && is_numeric($min)) {
                $this->min = $min;
            }
            if ($max !== '' && is_numeric($max)) {
                $this->max = $max;
            }
        }
    }

    function aliases()
    {
        $a1['id']   = 461;
        $a1['name'] = 'password';
        $a1['desc'] = 'Password Text Box';
        $a1['reqmodules'] = array('roles');
        return array($a1);
    }

    function setValue($value)
    {
         $this->value = $this->encrypt($value);
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (is_array($value) && $value[0] == $value[1]) {
            $value = $value[0];
        } else {
            $this->invalid = xarML('Passwords did not match');
            $this->value = null;
            return false;
        }

        if (!empty($value) && strlen($value) > $this->maxlength) {
            $this->invalid = xarML('password: must be less than #(1) characters long',$this->max + 1);
            $this->value = null;
            return false;
        } elseif (isset($this->min) && strlen($value) < $this->min) {
            $this->invalid = xarML('password: must be at least #(1) characters long',$this->min);
            $this->value = null;
            return false;
        } else {
            $this->value = $value;
            return true;
        }
    }

    public function encrypt($value = null)
    {
        if (empty($value)) return null;
        return md5($value);
    }


    public function showInput(Array $data = array())
    {
        extract($data);

        if (empty($maxlength) && isset($this->max)) {
            $this->maxlength = $this->max;
            if ($this->size > $this->maxlength) {
                $this->size = $this->maxlength;
            }
        }

        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['confirm']  = isset($confirm) ? $confirm : true;

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
