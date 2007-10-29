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

    public $linkrule   = 1;
    public $existrule  = 0;
    public $validationargs   = array('min','max','regex','linkrule','existrule');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'username';
        $this->filepath   = 'modules/roles/xarproperties';
        $this->parseValidation($this->validation);
    }

    public function validateValue($value = null)
    {
		// Save the incoming value until validation is successful
		$this->rawvalue = $value;

        // Validate as a text box
        if (!parent::validateValue($value)) return false;

        if (!isset($value)) {
            $value = $this->value;
        }

        /* CHECKME: was this good for anything?
        if (empty($value)) {
            $value = xarUserGetVar('id');
        }
		*/

        if (empty($value)) return true;

        $role = xarRoles::ufindRole($value);

        switch ((int)$this->existrule) {
        	case 1:
            if (!empty($role)) {
	            $this->invalid = xarML('user #(1) already exists', $value);
				return false;
            }
        	break;

        	case 2:
            if (empty($role)) {
	            $this->invalid = xarML('user #(1) does not exist', $value);
				return false;
            }
        	break;

        	case 0:
        	default:
        }

        /*
        // check that the user exists, but dont except
        if (is_numeric($value)) {
            try {
                $user = xarUserGetVar('uname', $value);
            } catch (NotFoundExceptions $e) {
                // Nothing to do?
            }
        } else {
            $role = xarRoles::findRole($value);
            if (!empty($role)) xarRoles::ufindRole($value);
            try {
                $user = $value;
                $value = $role->getID();
            } catch (NotFoundExceptions $e) {}
        }

        if (!is_numeric($value) || empty($user)) {
            $this->invalid = xarML('user: #(1)', $this->name);
            $this->value = null;
            return false;
        } else {
            $this->value = $value;
            return true;
        }
        */
		$this->value = empty($role) ? 0 : $role->getID();;
        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);
//        echo "X".$this->rawvalue."X";//exit;
        if (isset($this->rawvalue)) {
        	$data['value'] = $this->rawvalue;
        	$data['user'] = $this->rawvalue;
        } else {
			if (!isset($value)) $value = $this->value;
			if (empty($value))  {
				$data['user'] = '';
				$data['value']= 0;
			} else {
				try {
					$user = xarUserGetVar('name', $value);
					if (empty($user))
						$user = xarUserGetVar('uname', $value);
				} catch(NotFoundExceptions $e) {
					$user = $value;
				}

				$data['user'] = xarVarprepForDisplay($user);
				$data['value']= $value;
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

			try {
				$user = xarUserGetVar('name', $value);
				if (empty($user))
					$user = xarUserGetVar('uname', $value);
			} catch(NotFoundExceptions $e) {
				$user = $value;
			}

			$data['user']  = xarVarPrepForDisplay($user);
			$data['value'] = $value;

			if ($this->validation) {
				$data['linkurl'] = xarModURL('roles','user','display',array('id' => $value));
			} else {
				$data['linkurl'] = "";
			}
		}
        return parent::showOutput($data);
    }

    public function parseValidation($validation = '')
    {
        if (is_array($validation)) {
            $fields = $validation;
        } else {
            $fields = unserialize($validation);
        }
        if (!empty($fields) && is_array($fields)) {
            foreach ($this->validationargs as $validationarg) {
                if (isset($fields[$validationarg])) {
                    $this->$validationarg = $fields[$validationarg];
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

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }
        foreach ($this->validationargs as $validationarg) {
        	$data[$validationarg] = $this->$validationarg;
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
                foreach ($this->validationargs as $validationarg) {
                    if (isset($validation[$validationarg])) {
                        $data[$validationarg] = $validation[$validationarg];
                    }
                }
                $this->validation = serialize($data);

            } else {
                $this->validation = $validation;
            }
        }
        return true;
    }
}
?>
