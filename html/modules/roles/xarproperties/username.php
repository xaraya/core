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

    public $rawvalue   = null;

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
        // Save the incoming value until validation is successful
        $this->rawvalue = $value;

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
        // Cater to a common case
        if ((isset($data['user']) && $data['user'] == 'myself') || (isset($data['value']) && $data['value'] == 'myself')) {
            $this->value = xarUserGetVar('id');        
        }

        if (isset($this->rawvalue)) {
            $data['value'] = $this->rawvalue;
            $data['user'] = $this->rawvalue;
        } else {
            if (!isset($data['value'])) $data['value'] = $this->value;
            if (empty($data['value']))  {
                $data['user'] = '';
                $data['value']= 0;
            } else {
                if(is_numeric($data['value'])) {
                    try {
                        $user = xarUserGetVar('uname', $data['value']);
                        // Does this make sense? The user should already have been checked before storing
                        if (empty($user))
                            $user = xarUserGetVar('name', $data['value']);
                    } catch(NotFoundExceptions $e) {
                        $user = $data['value'];
                    }
                } else {
                    $user = $data['value'];
                }
                    $data['user'] = xarVarprepForDisplay($user);
            }
        }

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (isset($this->rawvalue)) {
            $data['value'] = $this->rawvalue;
            $data['user'] = $this->rawvalue;
        } else {
            extract($data);
            if (!isset($value)) $value = $this->value;
            if (empty($value))  $value = xarUserGetVar('id');
            if(is_numeric($value)) {
                try {
                    $user = xarUserGetVar('name', $value);
                    if (empty($user))
                        $user = xarUserGetVar('uname', $value);
                } catch(NotFoundExceptions $e) {
                    $user = $value;
                }
            } else {
                $user = $value;
            }

            $data['user']  = xarVarPrepForDisplay($user);
            $data['value'] = $value;

            if ($this->configuration) {
                $data['linkurl'] = xarModURL('roles','user','display',array('id' => $value));
            } else {
                $data['linkurl'] = "";
            }
        }
        return parent::showOutput($data);
    }
}
?>
