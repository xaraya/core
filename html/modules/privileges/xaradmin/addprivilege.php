<?php
/**
 * AddPrivilege - add a privilege to the repository
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * addPrivilege - add a privilege to the repository
 * This is an action page
 */
function privileges_admin_addprivilege()
{
    if(!xarVarFetch('pname',      'isset', $pname,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prealm',     'isset', $prealm,     'All', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',    'isset', $pmodule,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pcomponent', 'isset', $pcomponent, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptype',      'isset', $type,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('plevel',     'isset', $plevel,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pparentid',  'isset', $pparentid,  NULL, XARVAR_DONT_SET)) {return;}
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
    if (!xarSecConfirmAuthKey()) return;

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
        $pargs = array('name' => $pname,
                    'realm' => $prealm,
                    'module' => $pmodule,
                    'component' => $pcomponent,
                    'instance' => $instance,
                    'level' => $plevel,
                    'parentid' => $pparentid,
                    );
    }

//Call the Privileges class
    $priv = new xarPrivilege($pargs);

//Try to add the privilege and bail if an error was thrown
    if (!$priv->add()) {return;}

    xarSessionSetVar('privileges_statusmsg', xarML('Privilege Added',
                    'privileges'));

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'newprivilege'));
}

?>
