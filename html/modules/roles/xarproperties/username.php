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
    public $initialization_store_type     = 'name';

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

        // We allow the special value [All]
        
        if ($value != '[All]') {
        $role = xarRoles::ufindRole($value);        
            switch ((int)$this->validation_existrule) {
                case 1:
                if (!empty($role)) {
                    
                    // If we're just keeping the name we already have, it's OK
                    if ($this->value == $value) break;

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
        }
        $this->setValue($value);
        return true;
    }

    public function showInput(Array $data = array())
    {
        // The user param is a name
        if (isset($data['user'])) {
            // Cater to a common case
            if ($data['user'] == 'myself') {
                $this->value = xarUserGetVar('id');
                $role = xarRoles::get($this->value);
                $data['value'] = $role->getUser();
            } else {
                $data['value'] = $data['user'];
            }
        } else {
            if (isset($data['value'])) $this->value = $data['value'];
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
                $role = xarRoles::get($this->value);
                $data['value'] = $role->getUser();
            } else {
                $data['value'] = $data['user'];
            }
        } else {
            if (isset($data['value'])) $this->value = $data['value'];
            $data['value'] = $this->getValue();
        }

        if ($this->display_linkrule) {
            if ($this->initialization_store_type == 'id') {
                $textvalue = $this->value;
            } else {
                $textvalue = $this->value;
            }
            $data['linkurl'] = xarModURL('roles','user','display',array('id' => $this->value));
        } else {
            $data['linkurl'] = "";
        }
        return parent::showOutput($data);
    }
    
    public function getValue()
    {
        if ($this->initialization_store_type == 'id') {
            if ($this->value == 0) return '[All]';
            if(!is_numeric($this->value)) {
                throw new BadParameterException($this->value, 'is not a user ID');
            }
            return xarUserGetVar('uname',$this->value);
        } else {
            if (empty($this->value)) return '';
            return $this->value;
        }
    }

    public function setValue($value=null)
    {
        if ($this->initialization_store_type == 'id') {
            if (empty($value)) {
                $this->value = null;
            } else {
                if ($value == '[All]') {
                    $this->value = 0;
                } else {
                    $role = xarRoles::ufindRole($value);
                    $this->value = $role->getID();
                }
            }
        } else {
            $this->value = $value;
        }
    }
}
?>
