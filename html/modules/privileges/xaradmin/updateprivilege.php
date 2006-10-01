<?php
/**
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * updateprivilege - update a privilege
 */
function privileges_admin_updateprivilege()
{
// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    if(!xarVarFetch('pid',        'isset', $pid,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pname',      'isset', $name,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prealm',     'isset', $realm,     'All', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',    'isset', $module,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pcomponent', 'isset', $component,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptype',      'isset', $type,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('plevel',     'isset', $level,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',  'array', $pinstance, array(), XARVAR_NOT_REQUIRED)) {return;}

    $instance = "";
    foreach($pinstance as $part) $instance .= $part . ":";
    if ($instance =="") {
        $instance = "All";
    }
    else {
        $instance = substr($instance,0,strlen($instance)-1);
    }

// Security Check
    if(!xarSecurityCheck('EditPrivilege',0,'Privileges',$name)) return;

// call the Privileges class and update the values

    sys::import('modules.privileges.class.privileges');
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

    xarModCallHooks('item', 'update', $pid, '');

    xarSessionSetVar('privileges_statusmsg', xarML('Privilege Modified',
                    'privileges'));

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyprivilege', array('pid' => $pid)));
}

?>
