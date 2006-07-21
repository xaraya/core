<?php
/**
 * Dynamic Passbox property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Dynamic Passbox property
 * @author mikespub <mikespub@xaraya.com>
 */
include_once 'modules/base/xarproperties/Dynamic_TextBox_Property.php';
class Dynamic_PassBox_Property extends Dynamic_TextBox_Property
{
    public $size = 25;
    public $maxlength = 254;

    public $min = 5;
    public $max = null;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template ='password';
        $this->filepath   = 'modules/roles/xarproperties';

        // check validation for allowed min/max length (or values)
        if (!empty($this->validation) && strchr($this->validation,':')) {
            list($min,$max) = explode(':',$this->validation);
            if ($min !== '' && is_numeric($min)) {
                $this->min = $min; // could be int or float - cfr. FloatBox below
            }
            if ($max !== '' && is_numeric($max)) {
                $this->max = $max; // could be int or float - cfr. FloatBox below
            }
        }
    }

    static function getRegistrationInfo()
    {
        // make type password an alias, since it's a very common mistake
        $a1 = new PropertyRegistration();
        $a1->id = 461;
        $a1->name = 'password';
        $a1->desc = 'Password Text Box';

        $info = new PropertyRegistration();
        $info->reqmodules = array('roles');
        $info->id   = 46;
        $info->name = 'passbox';
        $info->desc = 'Password Text Box';
        $info->aliases = array($a1);
        $info->aliases = array($a1);

        return $info;
    }
    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (is_array($value) && $value[0] == $value[1]) {
            $value = $value[0];
        } else {
            $this->invalid = xarML('text : Passwords did not match');
            $this->value = null;
            return false;
        }

        if (!empty($value) && strlen($value) > $this->maxlength) {
            $this->invalid = xarML('text : must be less than #(1) characters long',$this->max + 1);
            $this->value = null;
            return false;
        } elseif (isset($this->min) && strlen($value) < $this->min) {
            $this->invalid = xarML('text : must be at least #(1) characters long',$this->min);
            $this->value = null;
            return false;
        } else {
    // TODO: allowable HTML ?
            $this->value = $value;
            return true;
        }
    }

    function showInput($data = array())
    {
        extract($data);

        if (empty($maxlength) && isset($this->max)) {
            $this->maxlength = $this->max;
            if ($this->size > $this->maxlength) {
                $this->size = $this->maxlength;
            }
        }

        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['confirm']  = !empty($confirm) ? true : false;

        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        //we don't really want to show the password, do we?
        $data['value'] = '';

        return parent::showOutput($data);
    }
}
?>
