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
 * new - create a new privilege
 * Takes no parameters
 * @return array|void data for the template display
 */
function privileges_admin_new()
{
    // Security
    if(!xarSecurity::check('AddPrivileges')) return;

    $data = array();

    if (!xarVar::fetch('id',         'isset', $data['id'],        '',          xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('pname',      'isset', $data['pname'],      '',         xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('pparentid',  'isset', $data['pparentid'],  '',         xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('prealm',     'isset', $data['prealm'],     'All',      xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('pmodule',    'isset', $data['pmodule'],    'All',      xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('pcomponent', 'isset', $data['pcomponent'], 'All',      xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('pinstance',  'isset', $data['pinstance'],  '',         xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('plevel',     'isset', $data['plevel'],     '',         xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('ptype',      'isset', $data['ptype'],      '',         xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('show',       'isset', $data['show'],       'assigned', xarVar::NOT_REQUIRED)) {return;}
    if (!xarVar::fetch('trees',      'isset', $trees,              NULL,       xarVar::NOT_REQUIRED)) {return;}

// Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

// remove duplicate entries from the list of privileges
    $privileges = array();
    $names = array();
    $privileges[] = array('id' => 0,
                            'name' => '');
    foreach(xarPrivileges::getprivileges() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names)){
            $names[] = $nam;
            $privileges[] = $temp;
        }
    }
    //Load Template
    $instances = xarMod::apiFunc('privileges','admin','getinstances',array('module' => $data['pmodule'],'component' => $data['pcomponent']));
// send to external wizard if necessary
    if (!empty($instances['external']) && $instances['external'] == "yes") {
        $data['target'] = $instances['target'] . '&amp;extpid=0&amp;extname='.$data['pname'].'&amp;extrealm='.$data['prealm'].'&amp;extmodule='.$data['pmodule'].'&amp;extcomponent='.$data['pcomponent'].'&amp;extlevel='.$data['plevel'];
        $data['instances'] = array();
    } else {
        $data['instances'] = $instances;
    }

    $accesslevels = SecurityLevel::$displayMap;
    unset($accesslevels[-1]);
    $data['levels'] = array();
    foreach ($accesslevels as $key => $value) $data['levels'][] = array('id' => $key, 'name' => $value);
    
    $data['authid'] = xarSec::genAuthKey();
    $data['realms'] = xarPrivileges::getrealms();
    $data['privileges'] = $privileges;
    $data['components'] = xarMod::apiFunc('privileges','admin','getcomponents',array('modid' => xarMod::getRegID($data['pmodule'])));
    return $data;
}
