<?php

    /**
     * returnPrivilege: adds or modifies a privilege coming from an external wizard .
     *
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
     * @param   strings with pid, name, realm, module, component, instance and level
     * @return  mixed id if OK, void if not
    */

    function privileges_adminapi_returnprivilege($args)
    {
        extract($args);

        if (!empty($instance) && is_array($instance)) {
            $instance = implode(':',$instance);
        }
        $instance = !empty($instance) ? $instance : "All";

        if(empty($pid)) {
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
            $priv = Privileges_Privileges::getPrivilege($pid);
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