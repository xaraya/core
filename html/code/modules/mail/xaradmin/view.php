<?php
/**
 * Queue management for mail module
 *
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 */
function mail_admin_view(Array $args=array())
{
     // Security
    if (!xarSecurity::check('AdminMail')) return;
     
    // Retrieve the object which holds our queue definition
    if(!$qdefInfo = xarMod::apiFunc('mail','admin','getqdef')) {
        return OfferCreate();
    } else {
        $data['qdef'] = $qdefInfo;
        if(!xarVar::fetch('itemid','int:1:',$data['itemid'],0,xarVar::NOT_REQUIRED)) return;
        return $data;
    }
}

/**
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 *
 * @return array data for the template display
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 */
function OfferCreate($qDef = null)
{
    $data['authid'] = xarSec::genAuthKey();
    $data['qdef_name'] = isset($qDef) ? $qDef : 'mailqueues';
    $data['qdef_method'] = 1;
    $data['qdef_create'] = array(array('id' => 1,'name' => xarML('Create new object with name')));
    $data['qdef_choose'] = array(array('id' => 2,'name' => xarML('Use an existing object')));
    return xarTpl::module('mail','admin','queue-newdef',$data);
}
?>
