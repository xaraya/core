<?php

    /**
     * returnPrivilege: adds or modifies a privilege coming from an external wizard .
     *
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   strings with id, name, realm, module, component, instances and level
     * @return  mixed id if OK, void if not
    */

    function privileges_adminapi_returnprivilege($args)
    {
        extract($args);
        
        $instance = implode(':',$instances);
        $instance = !empty($instance) ? $instabce : "All";

        if($id==0) {
            $pargs = array('name' => $name,
                           'realm' => $realm,
                           'module' => $module,
                           'module_id'=>xarMod::getID($module),
                           'component' => $component,
                           'instance' => $instance,
                           'level' => $level,
                           'parentid' => 0
                           );
            sys::import('modules.privileges.class.privilege');
            $priv = new xarPrivilege($pargs);
            if ($priv->add()) return $priv->getID();
        } else {
            sys::import('modules.privileges.class.privileges');
            $priv = xarPrivileges::getPrivilege($id);
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModule($module);
            $priv->setModuleID($module);
            $priv->setComponent($component);
            $priv->setInstance($instance);
            $priv->setLevel($level);
            if ($priv->update()) return $priv->getID();
        }
        return;
    }
?>
