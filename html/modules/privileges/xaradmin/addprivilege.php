<?php

/**
 * addPrivilege - add a privilege to the repository
 * This is an action page
 */
function privileges_admin_addprivilege()
{
    if(!xarVarFetch('pname',      'str', $pname,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('prealm',     'str', $prealm,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',    'str', $pmodule,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pcomponent', 'str', $pcomponent, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('ptype',      'str', $type,       NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('plevel',     'str', $plevel,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pparentid',  'str', $pparentid,  NULL, XARVAR_NOT_REQUIRED)) {return;}

    $i = 0;
    $instance = "";
    //Why using this instead of an array??
    // you can do in the form => <input type=whatever name="array[]">
    // And you will get an array back...
    while ( xarVarFetch('pinstance'.$i, $pinstance, NULL, XARVAR_NOT_REQUIRED) && $pinstance) {
        $i++;
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
