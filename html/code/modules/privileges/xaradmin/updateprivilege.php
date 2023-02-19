<?php
/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * updateprivilege - update a privilege
 */
function privileges_admin_updateprivilege()
{
    // Security
    if (!xarSecurity::check('EditPrivileges')) return; 
    
// Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

// Check for authorization code
    if (!xarSec::confirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if(!xarVar::fetch('id',         'isset', $id,        NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('pname',      'isset', $name,       NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('prealm',     'isset', $realm,     'All', xarVar::NOT_REQUIRED)) {return;}
    if(!xarVar::fetch('pmodule',    'isset', $pmodule,    'All',    xarVar::NOT_REQUIRED)) {return;}
    if(!xarVar::fetch('pcomponent', 'isset', $component,  'All', xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('ptype',      'isset', $type,       NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('plevel',     'isset', $level,      NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('pinstance',  'isset', $pinstance,  NULL, xarVar::NOT_REQUIRED)) {return;}

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
    if(!xarSecurity::check('EditPrivileges',0,'Privileges',$name)) return;

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

    xarModHooks::call('item', 'update', $id, '');

    xarSession::setVar('privileges_statusmsg', xarML('Privilege Modified',
                    'privileges'));

// redirect to the next page
    xarController::redirect(xarController::URL('privileges', 'admin', 'modifyprivilege', array('id' => $id)));
    return true;
}