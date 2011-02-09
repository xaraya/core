<?php
/**
 * Queue management for mail module
 *
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 */
function mail_admin_view(Array $args=array())
{
     // Security
    if (!xarSecurityCheck('AdminMail')) return;
     
    // Retrieve the object which holds our queue definition
    if(!$qdefInfo = xarMod::apiFunc('mail','admin','getqdef')) {
        return OfferCreate();
    } else {
        $data['qdef'] = $qdefInfo;
        if(!xarVarFetch('itemid','int:1:',$data['itemid'],0,XARVAR_NOT_REQUIRED)) return;
        return $data;
    }
}

/**
 * @return array data for the template display
 */
function OfferCreate($qDef = null)
{
    $data['authid'] = xarSecGenAuthKey();
    $data['qdef_name'] = isset($qDef) ? $qDef : 'mailqueues';
    $data['qdef_method'] = 1;
    $data['qdef_create'] = array(array('id' => 1,'name' => xarML('Create new object with name')));
    $data['qdef_choose'] = array(array('id' => 2,'name' => xarML('Use an existing object')));
    return xarTpl::module('mail','admin','queue-newdef',$data);
}
?>
