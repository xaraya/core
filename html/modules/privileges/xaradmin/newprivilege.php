<?php
/**
 * File: $Id:
 * 
 * Create a new privilege
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * newPrivilege - create a new privilege
 * Takes no parameters
 */
function privileges_admin_newprivilege()
{
    $data = array();

    if (!xarVarFetch('pid',        'isset', $data['pid'],        '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pname',      'isset', $data['pname'],      '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pparentid',  'isset', $data['pparentid'],  '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('prealm',     'isset', $data['prealm'],     'All',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pmodule',    'isset', $module,             NULL,       XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pcomponent', 'isset', $data['pcomponent'], 'All',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pinstance',  'isset', $data['pinstance'],  '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('plevel',     'isset', $data['plevel'],     '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('ptype',      'isset', $data['ptype'],      '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('show',       'isset', $data['show'],       'assigned', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('trees',      'isset', $trees,              NULL,       XARVAR_NOT_REQUIRED)) {return;}

    if ($module !== NULL) {$data['pmodule'] = strtolower($module);}
    else {$data['pmodule'] = 'All';}

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('AddPrivilege')) return;

// call the Privileges class
    $privs = new xarPrivileges();

// remove duplicate entries from the list of privileges
    $privileges = array();
    $names = array();
    $privileges[] = array('pid' => 0,
                            'name' => '');
    foreach($privs->getprivileges() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names)){
            $names[] = $nam;
            $privileges[] = $temp;
        }
    }

    //Load Template
    $instances = $privs->getinstances($data['pmodule'],$data['pcomponent']);
// send to external wizard if necessary
    if (!empty($instances['external']) && $instances['external'] == "yes") {
//    xarResponseRedirect($instances['target'] . "&extpid=0&extname=$name&extrealm=$realm&extmodule=$module&extcomponent=$component&extlevel=$level");
//        return;
        $data['target'] = $instances['target'] . '&amp;extpid=0&amp;extname='.$data['pname'].'&amp;extrealm='.$data['prealm'].'&amp;extmodule='.$data['pmodule'].'&amp;extcomponent='.$data['pcomponent'].'&amp;extlevel='.$data['plevel'];
        $data['instances'] = array();
    } else {
        $data['instances'] = $instances;
    }

    $data['authid'] = xarSecGenAuthKey();
    $data['realms'] = $privs->getrealms();
    $data['modules'] = $privs->getmodules();
    $data['privileges'] = $privileges;
    $data['components'] = $privs->getcomponents($data['pmodule']);
    return $data;
}


?>