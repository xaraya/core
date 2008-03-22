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
 * Handle Username Property
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.textbox');

class UsernameProperty extends TextBoxProperty
{
    public $id         = 7;
    public $name       = 'username';
    public $desc       = 'Username';
    public $reqmodules = array('roles');

    public $display_linkrule                = 0;
    public $validation_existrule            = 0;
    public $validation_existrule_invalid;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'username';
        $this->filepath   = 'modules/roles/xarproperties';

        // Cater to a common case
        if ($this->value == 'myself') $this->value = xarUserGetVar('id');        
    }

    public function validateValue($value = null)
    {
        // Validate as a text box
        if (!parent::validateValue($value)) return false;

        // We set an empty value to the id of the current user
        if (empty($value) || ($value == 'myself')) $value = xarUserGetVar('uname');

        $role = xarRoles::ufindRole($value);
        
        if (empty($role)) {
            $this->invalid = xarML('user #(1) does not exist', $value);
            return false;
        }

        switch ((int)$this->validation_existrule) {
            case 1:
            if (!empty($role)) {
                if (!empty($this->validation_existrule_invalid)) {
                    $this->invalid = xarML($this->validation_existrule_invalid);
                } else {
                    $this->invalid = xarML('user #(1) already exists', $value);
                }
                return false;
            }
            break;

            case 2:
            if (empty($role)) {
                if (!empty($this->validation_existrule_invalid)) {
                    $this->invalid = xarML($this->validation_existrule_invalid);
                } else {
                    $this->invalid = xarML('user #(1) does not exist', $value);
                }
                return false;
            }
            break;

            case 0:
            default:
        }
        $this->value = $role->getID();
        return true;
    }

    public function showInput(Array $data = array())
    {
        // The user param is a name
        if (isset($data['user'])) {
            // Cater to a common case
            if ($data['user'] == 'myself') {
                $this->value = xarUserGetVar('id');
                $data['value'] = $this->getValue();
            } else {
                $data['value'] = $data['user'];
            }
        } else {
            // The value param is a user ID
            if (isset($data['value'])) $this->value = $data['value'];
            if (empty($this->value))  {
                $this->value = '';
            } else {
                if(!is_numeric($this->value)) {
                    throw new BadParameterException($this->value, 'is not a user ID');
                }
            }
            $data['value'] = $this->getValue();
        }
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        // The user param is a name
        if (isset($data['user'])) {
            // Cater to a common case
            if ($data['user'] == 'myself') {
                $this->value = xarUserGetVar('id');
                $data['value'] = $this->getValue();
            } else {
                $data['value'] = $data['user'];
            }
        } else {
            // The value param is a user ID
            if (isset($data['value'])) $this->value = $data['value'];
            if (empty($this->value))  {
                $this->value = '';
            } else {
                if(!is_numeric($this->value)) {
                    throw new BadParameterException($this->value, 'is not a user ID');
                }
            }
            $data['value'] = $this->getValue();

            if ($this->configuration) {
                $data['linkurl'] = xarModURL('roles','user','display',array('id' => $value));
            } else {
                $data['linkurl'] = "";
            }
        }
        return parent::showOutput($data);
    }
    
    public function getValue()
    {
        return xarUserGetVar('uname',$this->value);
    }

    public function setValue($uname)
    {
        $role = xarRoles::ufindRole($uname);
        $this->value = $role->getID();
    }
}
?>
