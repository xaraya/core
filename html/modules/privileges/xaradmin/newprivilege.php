<?php
/**
 *
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
    if (!xarVarFetch('pmodule',    'isset', $data['pmodule'],    0,          XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pcomponent', 'isset', $data['pcomponent'], 'All',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pinstance',  'isset', $data['pinstance'],  '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('plevel',     'isset', $data['plevel'],     '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('ptype',      'isset', $data['ptype'],      '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('show',       'isset', $data['show'],       'assigned', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('trees',      'isset', $trees,              NULL,       XARVAR_NOT_REQUIRED)) {return;}

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('AddPrivilege')) return;

// remove duplicate entries from the list of privileges
    $privileges = array();
    $names = array();
    $privileges[] = array('pid' => 0,
                            'name' => '');
    foreach(xarPrivileges::getprivileges() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names)){
            $names[] = $nam;
            $privileges[] = $temp;
        }
    }

    //Load Template
    $instances = xarPrivileges::getinstances($data['pmodule'],$data['pcomponent']);
// send to external wizard if necessary
    if (!empty($instances['external']) && $instances['external'] == "yes") {
        $data['target'] = $instances['target'] . '&amp;extpid=0&amp;extname='.$data['pname'].'&amp;extrealm='.$data['prealm'].'&amp;extmodule='.xarModGetNameFromID($data['pmodule']).'&amp;extcomponent='.$data['pcomponent'].'&amp;extlevel='.$data['plevel'];
        $data['instances'] = array();
    } else {
        $data['instances'] = $instances;
    }

    $data['authid'] = xarSecGenAuthKey();
    $data['realms'] = xarPrivileges::getrealms();
    $data['privileges'] = $privileges;
    $data['components'] = xarPrivileges::getcomponents($data['pmodule']);
    return $data;
}


?>
