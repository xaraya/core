<?php
/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 */

sys::import('modules.dynamicdata.class.properties.base');

/**
 * Handle Access property
 */
class AccessProperty extends DataProperty
{
    public $id          = 30092;
    public $name        = 'access';
    public $desc        = 'Access';
    public $reqmodules  = array('privileges');

    public $group       = 0;
    public $level       = 100;
    public $failure     = 0;
    public $myself      = -6;
    public $allallowed  = array('Administrators');
    public $initialization_group_multiselect = false;
    public $validation_override              = false;


    public $module      = 'All';
    public $component   = 'All';
    public $instance    = 'All';

	/**
	 * Create an instance of this dataproperty<br/>
	 * - It belongs to the privileges module<br/>
	 * - It has its own input/output templates<br/>
	 * - it is found at modules/privileges/xarproperties<br/>
	 *
	 */	
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'privileges';
        $this->filepath  = 'modules/privileges/xarproperties';
        $this->template  = 'access';
    }
	
	/**
	 * Get the value of a dropdown from a web page<br/>
	 * Select one or multiple values from dropdown
	 * 
	 * @param  string name The name of the dropdown to be selected
	 * @param  string value The value of the dropdown to be selected
	 * @return bool   Returns true or false based on failure behavior
	 */	
    public function checkInput($name = '', $value = null)
    {
        /** @var SelectProperty $dropdown */
        $dropdown = DataPropertyMaster::getProperty(array('name' => 'dropdown'));        
        $value = array();
        
        // Check the group
        if ($this->initialization_group_multiselect) {
            /** @var MultiSelectProperty $multiselect */
            $multiselect = DataPropertyMaster::getProperty(array('name' => 'multiselect'));        
            $multiselect->options = $this->getgroupoptions();
            $multiselect->validation_override = $this->validation_override;
            if (!$multiselect->checkInput($name . '_group')) return false;
            $value['group'] = $multiselect->value;
            // The override is only meant for the groups
            $multiselect->validation_override = false;
        } else {
            $dropdown->options = $this->getgroupoptions();
            $dropdown->validation_override = $this->validation_override;
            if (!$dropdown->checkInput($name . '_group')) return false;
            $value['group'] = $dropdown->value;
            // The override is only meant for the groups
            $dropdown->validation_override = false;
        }
        
        // Check the level
        $dropdown->options = $this->getleveloptions();
        if (!$dropdown->checkInput($name . '_level')) return false;
        $value['level'] = $dropdown->value;
        
        // Check the failure behavior
        $dropdown->options = $this->getfailureoptions();
        if (!$dropdown->checkInput($name . '_failure')) return false;
        $value['failure'] = $dropdown->value;
        
        xarLog::message("DataProperty::validateValue: Skipping validation for " . $this->name, xarLog::LEVEL_DEBUG);
        $this->setValue($value);
        return true;
    }
	
	/**
	 * Display a dropdown for input
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */	
    public function showInput(Array $data = array())
    {
        if (isset($data['value'])) {
            $this->setValue($data['value']);
        } else {
            $this->setValue();
        }
        $value = $this->getValue();
        if (!isset($data['level'])) $data['level'] = $value['level'];
        if (!isset($data['group'])) $data['group'] = $value['group'];
        if (!isset($data['failure'])) {
            $data['failure'] = $value['failure'];
        } else {
            $data['showfailure'] = 1;
        }
        
        if (!isset($data['group_multiselect'])) {
            try {
                unserialize($data['group']);
                $data['group_multiselect'] = true;
            } catch(Exception $e) {
                $data['group_multiselect'] = false;
            }
        }
        if (!isset($value['group'])) $value['group'] = $data['group_multiselect'] ? array(0) : 0;
        
        if (!isset($data['groupoptions'])) $data['groupoptions'] = $this->getgroupoptions();
        if (!isset($data['leveloptions'])) $data['leveloptions'] = $this->getleveloptions();
        $data['failureoptions'] = $this->getfailureoptions();

        return parent::showInput($data);
    }
	
	/**
	 * Display a dropdown for output
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */	
    public function showOutput(Array $data = array())
    {
        if (isset($data['value'])) {
            $this->setValue($data['value']);
        } else {
            $this->setValue();
        }
        $value = $this->getValue();
        if (!isset($data['level'])) $data['level'] = (isset($value['level'])) ? $value['level'] : 800;
        if (!isset($data['group'])) $data['group'] = (isset($value['group'])) ? $value['group'] : array();
        if (!isset($data['failure'])) $data['failure'] = (isset($value['failure'])) ? $value['failure'] : 1;
        
        if (!isset($data['group_multiselect'])) {
            try {
                unserialize($data['group']);
                $data['group_multiselect'] = true;
            } catch(Exception $e) {
                $data['group_multiselect'] = false;
            }
        }
        if (!isset($value['group'])) $value['group'] = $data['group_multiselect'] ? array(0) : 0;
        
        if (!isset($data['groupoptions'])) $data['groupoptions'] = $this->getgroupoptions();
        if (!isset($data['leveloptions'])) $data['leveloptions'] = $this->getleveloptions();
        $data['failureoptions'] = $this->getfailureoptions();

        return parent::showOutput($data);
    }
	
	
	/**
	 * Get the dropdown groups and options
	 *
	 * @return array<mixed>    return the array key, value pairs.
	 */
    function getgroupoptions()
    {
        $anonID = xarConfigVars::get(null,'Site.User.AnonymousUID');
        $options = xarRoles::getgroups();
        $firstlines = array(
            array('id' => 0, 'name' => xarML('No requirement')),
            array('id' => $this->myself, 'name' => xarML('Current User')),
            array('id' => $anonID, 'name' => xarML('Users not logged in')),
            array('id' => -$anonID, 'name' => xarML('Users logged in')),
        );
        return array_merge($firstlines, $options);
    }
	
	/**
	 * Get the dropdown options that are on defined levels
	 * 
	 * @return array<mixed>    return the options with key as id and value as name
	 */	
    function getleveloptions()
    {
        sys::import('modules.privileges.class.securitylevel');
        $accesslevels = SecurityLevel::$displayMap;
        unset($accesslevels[-1]);
        $options = array();
        foreach ($accesslevels as $key => $value) $options[] = array('id' => $key, 'name' => $value);
        return $options;
    }
	
	/**
	 * Get the dropdown options that are failed
	 *
	 * @return array<mixed>    Returns the options that show failure and exception
	 */
    function getfailureoptions()
    {
        $options = array(
                        array('id' => 0, 'name' => xarML('Fail silently')),
                        array('id' => 1, 'name' => xarML('Throw exception')),
                    );
        return $options;
    }
	
	/**
	 * Set the value of input
	 * 
	 * @param  mixed value  The value of the input
	 */
    function setValue($value=null)
    {
        if (!empty($value) && !is_array($value)) {
            $this->value = $value;
        } else {
            if (empty($value)) {
                $value = array(
                    'group' => $this->group,
                    'level' => $this->level,
                    'failure' => $this->failure,
                );
            }
            $this->value = serialize($value);
        }
    }
		
	/**
	 * Get the value of input
	 * Unserialize the value of input
	 * handle the exception here
	 * 
	 * @return string   Returns the unserialized value
	 */	 
    public function getValue()
    {
        try {
            $value = unserialize($this->value);
        } catch(Exception $e) {
            $value = array(
                'group' => $this->group,
                'level' => $this->level,
                'failure' => $this->failure,
            );
        }
        return $value;
    }
	
	/**
	 * Check access from the access property
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @param  int exclusive 
	 * @return bool   Returns access(For group or level) if exclusive, otherwise returns false 
	 */
    public function check(Array $data=array(), $exclusive=1)
    {
        // Some groups always have access
        foreach ($this->allallowed as $allowed) {
            if (xarRoles::isParent($allowed, xarUser::getVar('uname'))) return true;
        }
        
        // We need to be in the correct realm
        if ($this->checkRealm($data)) {
            $disabled = false;

			if (isset($data['group'])) {
				if (is_string($data['group'])) {
					try {
						$groups = unserialize($data['group']);
					} catch (Exception $e) {
						$groups = $data['group'];
					}
				} else {
					$groups = $data['group'];
				}
			} else {
				// No group data found, take a default
				$groups = $this->group;
			}

            if (is_array($groups)){
                // This is a multiselect
                $this->initialization_group_multiselect = true;
                if (in_array(0,$groups)) $disabled = true;
            } else {
                // This is a dropdown
                $this->initialization_group_multiselect = false;
                if ((int)$groups == 0) $disabled = true;
                $groups = array($groups);
            }

            if ($exclusive) {
                // We check the level only if group access is disabled
                if (!$disabled) {
                    return $this->checkGroup($groups);
                } else {
                    return $this->checkLevel($data);
                }
            } else {
                // Both group access and level must be satisfied
                return $this->checkGroup($groups) && $this->checkLevel($data);
            }
        } else {
            return false;
        }
    }
    
	/**
	 * Check access (from the access tag
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @param  int exclusive 
	 * @return bool   Returns access(For group or level) if exclusive, otherwise returns false 
	 */
    public function checkAccessTag(Array $data=array(), $exclusive=1)
    {
        // Some groups always have access
        foreach ($this->allallowed as $allowed) {
            if (xarRoles::isParent($allowed, xarUser::getVar('uname'))) return true;
        }

        if (isset($data['exclusive'])) $exclusive = $data['exclusive'];
        
        // We need to be in the correct realm
        if ($this->checkRealm($data)) {
            $groups = array();
            if (isset($data['group'])) {
                if (!is_array($data['group'])) {
                    $groupsarray = explode(',', $data['group']);
                    $groupsdata = xarRoles::getgroups();
                    foreach ($groupsarray as $group) {
                        $group = trim($group);
                        foreach ($groupsdata as $groupdata) {
                            if ($groupdata['name'] == $group) {
                                $groups[] = $groupdata['id'];
                                break;
                            }
                        }
                    }
                } else {
                    $groups = $data['group'];
                }
            }

            if ($exclusive) {
                // We check the level only if group access is disabled, or there are no groups
                // CHECKME: review this
                $disabled = false;
                if (!$disabled) {
                    if (isset($data['group'])) {
                        return $this->checkGroup($groups);
                    } else {
                        return $this->checkLevel($data);
                    }
                } else {
                    return $this->checkLevel($data);
                }
            } else {
                // Both group access and level must be satisfied
                if (isset($data['group'])) return $this->checkGroup($groups) && $this->checkLevel($data);
                return false;
            }
        } else {
            return false;
        }
    }
    
	/**
	 * Check the realm of data
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return bool   Returns true
	 */	
    public function checkRealm(Array $data=array())
    {
        // CHECKME
        return true;
    }
    
	/**
	 * Check the level of data of options
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return bool   Returns true or false 
	 */	
    public function checkLevel(Array $data=array())
    {
        if (isset($data['level']))     $this->level = (int)$data['level'];
        if (isset($data['module']))    $this->module = $data['module'];
        if (isset($data['component'])) $this->component = $data['component'];
        if (isset($data['instance']))  $this->instance = $data['instance'];

        $access = false;
        if (xarSecurity::check('', 
                          0, 
                          $this->component, 
                          $this->instance, 
                          $this->module, 
                          '',
                          0,
                          $this->level)) {$access = true;
        }
        return $access;
    }
		
	/**
	 * Check the group of options
	 * 
	 * @param  array groups An array of input parameters
	 * @return bool   Returns true or false 
	 */	    
    public function checkGroup(Array $groups=array())
    {
        if (count($groups) > 1) {
            $this->initialization_group_multiselect = true;
        }
        $access = $this->checkGroupArray($groups);
        return $access;
    }
	
	/**
	 * Check an array of groups IDs
	 * 
	 * @param  array groups An array of input parameters (integers)
	 * @return bool   Returns true or false 
	 */	    
    private function checkGroupArray(Array $groups=array())
    {
        $anonID = xarConfigVars::get(null,'Site.User.AnonymousUID');
        $access = false;
        foreach ($groups as $group) {
            $group = (int)$group;
            if ($group == $this->myself) {
                $access = true;
            } elseif ($group == $anonID) {
                if (!xarUser::isLoggedIn()) $access = true;
            } elseif ($group == -$anonID) {
                if (xarUser::isLoggedIn()) $access = true;
            } elseif ($group) {
                $rolesgroup = xarRoles::getRole($group);
                $thisuser = xarRoles::current();
                if (is_object($rolesgroup)) {
                    if ($thisuser->isAncestor($rolesgroup)) $access = true;
                } 
            }
            if ($access) break;
        }
        return $access;
    }

	/**
	 * Used to show the hidden data
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 */	   	
    public function showHidden(Array $data = array())
    {
        if (isset($data['value'])) {
            $this->setValue($data['value']);
        } else {
            $this->setValue();
        }
        $value = $this->getValue();
        if (!isset($data['level'])) $data['level'] = $value['level'];
        if (!isset($data['group'])) $data['group'] = $value['group'];
        if (!isset($data['failure'])) {
            $data['failure'] = $value['failure'];
        } else {
            $data['showfailure'] = 1;
        }
        
        if (!isset($data['group_multiselect'])) {
            try {
                unserialize($data['group']);
                $data['group_multiselect'] = true;
            } catch(Exception $e) {
                $data['group_multiselect'] = false;
            }
        }
        if (!isset($value['group'])) $value['group'] = $data['group_multiselect'] ? array(0) : 0;
        
        if (!isset($data['groupoptions'])) $data['groupoptions'] = $this->getgroupoptions();
        if (!isset($data['leveloptions'])) $data['leveloptions'] = $this->getleveloptions();
        $data['failureoptions'] = $this->getfailureoptions();

        return parent::showHidden($data);
    }    
}

sys::import('modules.dynamicdata.class.properties.interfaces');

/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 */
class AccessPropertyInstall extends AccessProperty implements iDataPropertyInstall
{
	/**
	 * Give access to install property
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return bool   Returns true or false 
	 */	 
    public function install(Array $data=array())
    {
        $dat_file = sys::code() . 'modules/privileges/xardata/privileges_access_configurations-dat.xml';
        $data = array('file' => $dat_file);
        try {
            $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }
}
