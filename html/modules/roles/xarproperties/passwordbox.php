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

    public $password = null;

    public $config_min     = 4;
    public $config_max     = 30;
    public $config_regex   = null;
    public $config_other   = null;
    public $config_confirm = 0;

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

    function setValue($value)
    {
         $this->value = $this->encrypt($value);
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) $value = "";

       if ($this->config_confirm) {
            if (is_array($value) && $value[0] == $value[1]) {
                $value = $value[0];
            } else {
                $this->invalid = xarML('Passwords did not match');
                $this->value = null;
                return false;
            }
        }

        if (!(empty($value) && !empty($this->value))) {
            if (strlen($value) > $this->config_max) {
                $this->invalid = xarML('password: must be less than #(1) characters long', $this->config_max + 1);
                $this->value = null;
                return false;
            } elseif (isset($this->config_min) && strlen($value) < $this->config_min) {
                $this->invalid = xarML('password: must be at least #(1) characters long', $this->config_min);
                $this->value = null;
                return false;
            } else {
                $this->password = $value;
                $this->setValue($value);
            }
            if (!empty($this->regex)){
               preg_match($this->regex, $value,$matches);
               if (empty($matches)){
                   $this->invalid = xarML('#(1) text: does not match required pattern', $this->name);
                   $this->value = null;
                   return false;
               }
           }
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
        extract($data);

        // Get the properties for the form
        $properties = $this->getConfigProperties();
        foreach ($properties as $name => $configarg)
            $data[$name]   = $configarg;
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        //we don't really want to show the password, do we?
        $data['value'] = ' ';

        return parent::showOutput($data);
    }

    public function parseValidation($validation = '')
    {
        if (is_array($validation)) {
            $fields = $validation;
        } elseif (empty($validation)) {
            return true;
        } else {
            $fields = unserialize($validation);
        }
        if (!empty($fields) && is_array($fields)) {
            $properties = $this->getConfigProperties();
            foreach ($properties as $name => $configarg) {
                if (isset($fields[$name])) {
                    $fullname = 'config_' . $name;
                    $this->$fullname = $fields[$name];
                }
            }
        }
    }

    public function showValidation(Array $args = array())
    {
        extract($args);
        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['size']       = !empty($size) ? $size : 50;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (!isset($validation)) $validation = $this->validation;
        $this->parseValidation($validation);
        $properties = $this->getConfigProperties();
        foreach ($properties as $name => $configarg) {
            $data[$name] = $configarg;
        }
        // allow template override by child classes
        $module    = empty($module)   ? $this->getModule()   : $module;
        $template  = empty($template) ? $this->getTemplate() : $template;

        return xarTplProperty($module, $template, 'validation', $data);
    }

    public function updateValidation(Array $args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;

        // do something with the validation and save it in $this->validation
        if (isset($validation)) {
            if (is_array($validation)) {
                $data = array();
                $properties = $this->getConfigProperties();
                foreach ($properties as $name => $configarg) {
                    if (isset($validation[$name])) {
                        $data[$name] = $validation[$name];
                    }
                }
                $this->validation = serialize($data);

            } else {
                $this->validation = $validation;
            }
        }
        return true;
    }

    public function getConfigProperties()
    {
        $configproperties = array();
        $properties = $this->getPublicProperties();
        foreach ($properties as $name => $configarg) {
            if (substr($name,0,7) != 'config_') continue;
            $configproperties[substr($name,7)] = $configarg;
        }
        return $configproperties;
    }
}
?>