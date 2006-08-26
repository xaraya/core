<?php
/**
 * Dynamic data object execution
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 */

/**
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.dynamicdata.class.simpleinterface');
function dynamicdata_object_runmethod($args)
{
    if (empty($args['moduleid']) && !empty($args['module'])) {
       $args['moduleid'] = xarModGetIDFromName($args['module']);
    }
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }
    $interface = new Simple_Object_Interface($args);

    // let the interface handle the rest
    return $interface->handle($args);

    extract($args);
    unset($args['tplmodule']);
    unset($args['object']);
    unset($args['ddfunc']);

    $args['modid'] = xarModGetIDFromName($tplmodule);
    $info = xarModAPIFunc('dynamicdata', 'user', 'getObjectInfo',array('name' => $object));
    $args['itemtype'] = $info['itemtype'];
    // Get both variants just to be sure
    $args['modid'] = $info['moduleid'];
    $args['moduleid'] = $info['moduleid'];
    if (in_array($ddfunc, array('view','display'))) $type = 'user';
    else $type = 'admin';
    return xarModFunc('dynamicdata', $type, $ddfunc,$args);
}

?>
