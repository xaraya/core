<?php
/**
 * View the defined realms
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
 * viewRealms - view the defined realms
 * Takes no parameters
 */
function privileges_admin_viewrealms()
{
    $data = array();

    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Security Check
    if(!xarSecurityCheck('ViewPrivileges',0,'Realm')) return;

    $xartable =& xarDBGetTables();
    $q = new xarQuery('SELECT',$xartable['security_realms']);
    $q->addfields(array('xar_rid AS rid', 'xar_name AS name'));
    $q->setorder('xar_name');
    if(!$q->run()) return;

    $data['realms'] = $q->output();
    return $data;
}


?>