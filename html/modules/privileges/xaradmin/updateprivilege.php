<?php

/**
 * updateprivilege - update a privilege
 */
function privileges_admin_updateprivilege()
{
// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    list($pid,
         $name,
         $realm,
         $module,
         $component,
         $type,
         $level) = xarVarCleanFromInput('pid',
                                       'pname',
                                       'prealm',
                                       'pmodule',
                                       'pcomponent',
                                       'ptype',
                                       'plevel');

    $i = 0;
    $instance = "";
    while ($pinstance = xarVarCleanFromInput('pinstance'.$i)) {
        $i++;
        $instance .= $pinstance . ":";
    }
    if ($instance =="") {
        $instance = "All";
    }
    else {
        $instance = substr($instance,0,strlen($instance)-1);
    }

// Security Check
    if(!xarSecurityCheck('EditPrivilege',0,'Privileges',$name)) return;

// call the Privileges class and update the values

    if ($type =="empty") {

// this is just a container for other privileges
        $privs = new xarPrivileges();
        $priv = $privs->getPrivilege($pid);
        $priv->setName($name);
        $priv->setRealm('All');
        $priv->setModule('empty');
        $priv->setComponent('All');
        $priv->setInstance('All');
        $priv->setLevel(0);
    }
    else {
        $privs = new xarPrivileges();
        $priv = $privs->getPrivilege($pid);
        $priv->setName($name);
        $priv->setRealm($realm);
        $priv->setModule($module);
        $priv->setComponent($component);
        $priv->setInstance($instance);
        $priv->setLevel($level);
    }

//Try to update the privilege to the repository and bail if an error was thrown
    if (!$priv->update()) {return;}

    xarSessionSetVar('privileges_statusmsg', xarML('Privilege Modified',
                    'privileges'));

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'newprivilege'));
}

?>