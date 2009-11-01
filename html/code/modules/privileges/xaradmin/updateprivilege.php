<?php
/**
 * @package core modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
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
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if(!xarVarFetch('id',        'isset', $id,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pname',      'isset', $name,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prealm',     'isset', $realm,     'All', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',    'isset', $pmodule,    'All',    XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pcomponent', 'isset', $component,  'All', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptype',      'isset', $type,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('plevel',     'isset', $level,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',  'isset', $pinstance,  NULL, XARVAR_NOT_REQUIRED)) {return;}

    $instance = "";
    if (!empty($pinstance)) {
        if (is_array($pinstance)) {
            $instance = implode(':', $pinstance);
        } else {
            // for wizard-based privileges
            $instance = $pinstance;
        }
    }
    if ($instance =="") {
        $instance = "All";
    }

// Security Check
    if(!xarSecurityCheck('EditPrivilege',0,'Privileges',$name)) return;

// call the Privileges class and update the values

    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getPrivilege($id);
    if ($type =="empty") {

// this is just a container for other privileges
        $priv->setName($name);
        $priv->setRealm('All');
        $priv->setModuleID(null);
        $priv->setComponent('All');
        $priv->setInstance('All');
        $priv->setLevel(0);
    } else {
        $priv->setName($name);
        $priv->setRealm($realm);
        $priv->setModuleID($pmodule);
        $priv->setComponent($component);
        $priv->setInstance($instance);
        $priv->setLevel($level);
    }

//Try to update the privilege to the repository and bail if an error was thrown
    if (!$priv->update()) {return;}

    xarModCallHooks('item', 'update', $id, '');

    xarSession::setVar('privileges_statusmsg', xarML('Privilege Modified',
                    'privileges'));

// redirect to the next page
    xarResponse::Redirect(xarModURL('privileges', 'admin', 'modifyprivilege', array('id' => $id)));
}

?>
