<?php
/* Include the base class */
sys::import('modules.base.xarproperties.textbox');

/**
 * The UserName property handles a Xaraya user name
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
class UsernameProperty extends TextBoxProperty
{
    public $id         = 7;
    public $name       = 'username';
    public $desc       = 'Username';
    public $reqmodules = array('roles');

    public $display_linkurl                 = 0;
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
        if ($this->value == 'myself') $this->value = xarUser::getVar('id');        
    }

	/**
	 * Validate the value of a textbox
	 *
	 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
	 */
    public function validateValue($value = null)
    {
        // Save the current value of this property for comparison below
        $previousvalue = $this->value;
        
        // Validate as a text box
        if (!parent::validateValue($value)) return false;

        // We set an empty value to the id of the current user
        if (empty($value) || ($value == 'myself')) $value = xarUser::getVar('uname');

        // We allow the special value [All]
        
        if ($value != '[All]') {
            $role = xarRoles::ufindRole($value, xarRoles::ROLES_USERTYPE, xarRoles::ROLES_STATE_ALL);
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
                    xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
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
                    xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
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

	/**
	 * Display a textbox for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        // The user param is a name
        if (isset($data['user'])) {
            // Cater to a common case
            if ($data['user'] == 'myself') {
                $this->value = xarUser::getVar('id');
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

	/**
	 * Display a textbox for output
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */	
    public function showOutput(Array $data = array())
    {
        if (!empty($data['display_type'])) $this->initialization_display_name = $data['display_type'];
        if (!empty($data['link_url'])) $this->display_linkurl = $data['link_url'];
        
        // The user param is a name
        if (isset($data['user'])) {
            // Cater to a common case
            if ($data['user'] == 'myself') {
                if ($this->initialization_display_name == 'name')
                    $data['value'] = xarUser::getVar('name');
                else
                    $data['value'] = xarUser::getVar('uname');
            } else {
                $data['value'] = $data['user'];
            }
            $this->display_linkurl = 0;
        } elseif (isset($data['id'])) {
            // The value param is an ID
            $this->value = $data['id'];
            $store_type = $this->initialization_store_type;
            $this->initialization_store_type = 'id';
            $data['value'] = $this->getValue();
            $this->initialization_store_type = $store_type;
        } elseif (isset($data['value'])) {
            $this->value = $data['value'];
            $data['value'] = $this->getValue();
        } else {
            $this->value = xarUser::getVar('id');
            $data['value'] = xarUser::getVar('uname');
        }

        if ($this->display_linkurl) {
            if ($this->initialization_store_type == 'id') {
                $textvalue = $this->value;
            } else {
                $textvalue = $this->value;
            }
            $data['link_url'] = xarModURL('roles','user','display',array('id' => $this->value));
        } else {
            $data['link_url'] = "";
        }
        return parent::showOutput($data);
    }
    
	/**
	 * Used to show the hidden data
	 * 
	 * @param  array data An array of input parameters 
	 */	
    public function showHidden(Array $data = array())
    {
        if (empty($data['value'])) {
            $data['value'] = $this->getValue();
        }
        return parent::showHidden($data);
    }
    
	/**
	 * Get the value of input
	 *  Check the value of input whether it is numeric or not.
	 * 
	 * @return string    return value
	 */	
    public function getValue()
    {
        if ($this->initialization_store_type == 'id') {
            if(!is_numeric($this->value)) return $this->value;
            if ($this->value == 0) return '[All]';
            return xarUser::getVar('uname',$this->value);
        } else {
            if (empty($this->value)) return '';
            return $this->value;
        }
    }

	/**
	 * Set the value of input
	 * 
	 * @param  string value The value of the input
	 * @return string    return a storable representation of a value
	 */	
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