<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
    public $validation_existrule            = 0;    // 0: no rule; 1: must not already exist; 2: must already exist
    public $validation_existrule_invalid;
    public $initialization_store_type       = 'name';
    public $initialization_display_name     = 'uname';

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
        // Save the current value of this property for comparison below
        $previousvalue = $this->value;
        
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
                    if ($previousvalue == $value) break;

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
        if (!empty($data['display_name'])) $this->initialization_display_name = $data['display_type'];
        
        // The user param is a name
        if (isset($data['user'])) {
            // Cater to a common case
            if ($data['user'] == 'myself') {
                if ($this->initialization_display_name == 'name')
                    $data['value'] = xarUserGetVar('name');
                else
                    $data['value'] = xarUserGetVar('uname');
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
            if(!is_numeric($this->value)) return $this->value;
            if ($this->value == 0) return '[All]';
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
                    if (empty($role)) $this->value = null;
                    else $this->value = $role->getID();
                }
            }
        } else {
            $this->value = $value;
        }
    }
}
?>