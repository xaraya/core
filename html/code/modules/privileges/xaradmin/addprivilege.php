<?php
/**
 * AddPrivilege - add a privilege to the repository
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
 * addPrivilege - add a privilege to the repository
 * This is an action page
 */
function privileges_admin_addprivilege()
{
    // Security
    if (!xarSecurityCheck('AddPrivileges')) return; 
    
    if(!xarVarFetch('pname',      'isset', $pname,      NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prealm',     'isset', $prealm,     'All', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',    'isset', $pmodule,    'All', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pcomponent', 'isset', $pcomponent, NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptype',      'isset', $type,       NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('plevel',     'isset', $plevel,     NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pparentid',  'isset', $pparentid,  NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',  'array', $pinstances, array(), XARVAR_NOT_REQUIRED)) {return;}

    $instance = "";
    foreach ($pinstances as $pinstance) {
        $instance .= $pinstance . ":";
    }
    if ($instance =="") {
        $instance = "All";
    }
    else {
        $instance = substr($instance,0,strlen($instance)-1);
    }

// Check for authorization code
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if ($type =="empty") {

// this is just a container for other privileges
        $pargs = array('name' => $pname,
                    'realm' => 'All',
                    'module' => 'empty',
                    'component' => 'All',
                    'instance' => 'All',
                    'level' => 0,
                    'parentid' => 'All',
                    );
    }
    else {

// this is privilege has its own rights assigned
        $pargs = array('name'   => $pname,
                    'realm'     => $prealm, // now has realm id in it!!!
                    'module'    => $pmodule,
                    'component' => $pcomponent,
                    'instance'  => $instance,
                    'level'     => $plevel,
                    'parentid'  => $pparentid,
                    );
    }

//Call the Privileges class
    sys::import('modules.privileges.class.privilege');
    $priv = new xarPrivilege($pargs);

//Try to add the privilege and bail if an error was thrown
    if (!$priv->add()) {return;}

    xarSession::setVar('privileges_statusmsg', xarML('Privilege Added',
                    'privileges'));

// redirect to the next page
    xarController::redirect(xarModURL('privileges', 'admin', 'new'));
    return true;
}

?>
