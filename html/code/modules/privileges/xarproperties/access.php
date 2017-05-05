<?php
/* Include the parent class  */
sys::import('modules.dynamicdata.class.properties.base');

/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 */

/**
 * The access dataproperty defines and checks user access to code or to dataobjects<br/>
 * It is somewhat different from Xaraya's "traditional" privileges system in that the access checks are not named objects (Xaraya's masks)<br/>
 * If it is bound to a dataobject, the access property checks whether the user has access to a given item.<br/>
 *
 * The property's check method checks if a user: <nr/>
 * - belongs to a given roles group or
 * - has a given access level (VIEW, EDIT, DELETE etc.) or
 * - both
 *
 * (Note: there is a provision to include realms in the check, but this is not currently used)
 *
 * Access can be checked according to the needs of the situation. The usual checks for a bound access property are:
 * - Display access: display a given item of the dataobject the access property is bound to
 * - Modify access: modify a given item of the dataobject the access property is bound to
 * - Add access: Create an item of the dataobject the access property is bound to
 * - Delete access: delete a given item of the dataobject the access property is bound to
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
 * Get the dataproperty's values from a web page<br/>
 * The access property has 3 values:
 * - the value of its group multiselect
 * - the value of its access level dropdown
 * - the value of its exceptions radio buttons
 * 
 * @param  string name The dataproperty's name on the web page
 * @param  string value The dataproperty's value on the web page (this parameter is always ignored, as the method uses adhoc code to get the values of this property)
 * @return bool   This method passes the value gotten to the validateValue method and returns its output.
 *
 * See showInput method for a description what this dataproperty displays.
 */	
    public function checkInput($name = '', $value = null)
    {
        $dropdown = DataPropertyMaster::getProperty(array('name' => 'dropdown'));        
        $value = array();
        
        // Check the group
        if ($this->initialization_group_multiselect) {
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
        
        xarLog::message("DataProperty::validateValue: Skipping validation for " . $this->name);
        $this->setValue($value);
        return true;
    }
	
/**
 * Display the access property for input on a web page
 *
 * This property displays
 * - A multiselect with all the site's groups, and in addition the liens "No requirement", "Current User", "Users not logged in" and "Users logged in".
 * - A dropdown of Xaraya's privileges access level (0 - 800). In addition there is also a line "No Access"
 * - Radio buttons "Fail silently" and "Throw excpetion"
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the dataproperty for input on a web page
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
 * Display the access property's output on a web page
 * 
 * This property displays the output templates of its three components one below the other:
 * - the group multiselect
 * - the level dropdown
 * - the exception radio button chosen
 *
 * See the showInput method for details
 *
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the dataproperty for output on a web page
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
 * Get the options for this dataproperty's group multiselect
 * 
 * @return array  return the array key, value pairs.
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
 * Get the options for this dataproperty's level dropdown
 * 
 * @return array    return the options with key as id and value as name
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
 * Get the options for this dataproperty's exception radio buttons
 * 
 * @return array    return the options that show failure and exception
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
 * Set the value of this dataproperty
 *
 * This method creates a storable representation of a value and assigns it to the dataproperty's value
 *
 * @param  string value The value of the input
 * @return boolean    returns true
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
        return true;
    }
		
/**
 * Get the value of this dataproperty
 * 
 * This method takes the storable value of the property and transforms it for use in code or in a template
 * 
 * @return string    return a (non storable) value of the dataproperty 
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
 * Check whether the current user has access
 *
 * By default, the access level is only checked if the group check is disabled.
 *
 * @param  array data An array of input parameters
 * @param  string exclusive 
 * @return bool   Returns true if data is in the correct realm otherwise return false
 */
    public function check(Array $data=array(), $exclusive=1)
    {
        // Some groups always have access
        foreach ($this->allallowed as $allowed) {
            if (xarIsParent($allowed, xarUserGetVar('uname'))) return true;        
        }
        
        // We need to be in the correct realm
        if ($this->checkRealm($data)) {
            $disabled = false;
            try {
                if (isset($data['group'])) $group = unserialize($data['group']);
                else $group = $this->group;
            } catch (Exception $e) {
                $group = $data['group'];
            }
            if (is_array($group)){
                // This is a multiselect
                $this->initialization_group_multiselect = true;
                if (in_array(0,$group)) $disabled = true;
            } else {
                // This is a dropdown
                $this->initialization_group_multiselect = false;
                if ((int)$group == 0) $disabled = true;
                $group = array($group);
            }

            if ($exclusive) {
                // We check the level only if group access is disabled
                if (!$disabled) {
                    return $this->checkGroup($group);
                } else {
                    return $this->checkLevel($data);
                }
            } else {
                // Both group access and level must be satisfied
                return $this->checkGroup($group) && $this->checkLevel($data);
            }
        } else {
            return false;
        }
    }
    
/**
 * Check whether the current user belongs to a realm that has access
 * 
 * This is currently not used
 *
 * @param  array data An array of input parameters
 * @return bool   Returns true
 */	
    public function checkRealm(Array $data=array())
    {
        return true;
    }
    
/**
 * Check whether the current user has the proper access level for access
 * 
 * @param  array data An array of input parameters
 * @return bool   Returns true or false 
 */	
    public function checkLevel(Array $data=array())
    {
        if (isset($data['level']))     $this->level = (int)$data['level'];
        if (isset($data['module']))    $this->module = $data['module'];
        if (isset($data['component'])) $this->component = $data['component'];
        if (isset($data['instance']))  $this->instance = $data['instance'];

        $access = false;
        if (xarSecurityCheck('', 
                          0, 
                          $this->component, 
                          $this->instance, 
                          $this->module, 
                          '',
                          0,
                          $this->level)) {
            $access = true;
        }
        return $access;
    }
		
/**
 * Check whether the current user belongs to a group that has access
 * 
 * @param  array groups An array of input parameters
 * @return bool   Returns true or false 
 */	    
    public function checkGroup(Array $groups=array())
    {
        $anonID = xarConfigVars::get(null,'Site.User.AnonymousUID');
        $access = false;
        if (is_array($groups)) $this->initialization_group_multiselect = true;
        if ($this->initialization_group_multiselect) {
            foreach ($groups as $group) {
                $group = (int)$group;
                if ($group == $this->myself) {
                    $access = true;
                } elseif ($group == $anonID) {
                    if (!xarUserIsLoggedIn()) $access = true;
                } elseif ($group == -$anonID) {
                    if (xarUserIsLoggedIn()) $access = true;
                } elseif ($group) {
                    $rolesgroup = xarRoles::getRole($group);
                    $thisuser = xarCurrentRole();
                    if (is_object($rolesgroup)) {
                        if ($thisuser->isAncestor($rolesgroup)) $access = true;
                    } 
                }
                if ($access) break;
            }
        } else {
            $group = (int)$groups;
            if ($group == $this->myself) {
                $access = true;
            } elseif ($group == $anonID) {
                if (!xarUserIsLoggedIn()) $access = true;
            } elseif ($group == -$anonID) {
                if (xarUserIsLoggedIn()) $access = true;
            } elseif ($group) {
                $rolesgroup = xarRoles::getRole($group);
                $thisuser = xarCurrentRole();
                if (is_object($rolesgroup)) {
                    if ($thisuser->isAncestor($rolesgroup)) $access = true;
                } 
            }
        }
        return $access;
    }
	
/**
 * Display the access property inputs as hidden on a web page
 *
 * The hidden property "displays" (in hidden form) its inputs on the pagein order to make sure that checkInput can find an input for this property on submit (and so avoid an exception)
 *
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the hidden form of the dataproperty for input on a web page
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

/* Include the parent class  */
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
 * Load the configuration options of this dataproperty<br/>
 * The options are accessible in the dataproperty's configuration settings in the dynamicdata backend.
 *
 * Currently the only configuration option is an array of groups that bypass the check method (always have access). 
 * The default value of this is the Administrators group.
 * 
 * @param  array data An array of input parameters
 * @return bool  Returns true
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
?>
