<?php
/**
 * Update users from roles_admin_showusers
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/*
 * Update users from roles_admin_showusers
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_asknotification(Array $args=array())
{
    // Security
    if (!xarSecurityCheck('EditRoles')) return;
    
    // Get parameters
    if (!xarVarFetch('phase',    'str:0:', $data['phase'],    'display', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('mailtype', 'str:0:', $data['mailtype'], 'blank', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('id',       'isset',  $id,              NULL,    XARVAR_NOT_REQUIRED)) return;
    //Maybe some kind of return url will make this function available for other modules
    if (!xarVarFetch('state',    'int:0:', $data['state'],  xarRoles::ROLES_STATE_CURRENT, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('groupid',  'int:0:', $data['groupid'], 0,    XARVAR_NOT_REQUIRED)) return;
    //optional value
    if (!xarVarFetch('pass',     'str:0:', $data['pass'],     NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ip',       'str:0:', $data['ip'],       NULL, XARVAR_NOT_REQUIRED)) return;
    switch ($data['phase']) {
        case 'display' :
                $data['pass'] = xarSession::getVar('tmppass');
                xarSessionDelVar('tmppass');
                if ($data['mailtype'] == 'blank') {
                    $data['subject'] = '';
                    $data['message'] = '';
                } else {
                    $strings = xarMod::apiFunc('roles','admin','getmessagestrings', array('template' => $data['mailtype']));
                    if (!isset($strings)) return;

                    $data['subject'] = $strings['subject'];
                    $data['message'] = $strings['message'];
                }
                //Display the notification form
                if (!xarVarFetch('subject', 'str:1:', $data['subject'], $data['subject'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('message', 'str:1:', $data['message'], $data['message'], XARVAR_NOT_REQUIRED)) return;
                $data['authid'] = xarSecGenAuthKey();
                $data['id'] = base64_encode(serialize($id));

                // dynamic properties (if any)
                $data['properties'] = null;
                if (xarMod::isAvailable('dynamicdata')) {
                    // get the DataObject defined for this module (and itemtype, if relevant)
                    $object = xarMod::apiFunc('dynamicdata', 'user', 'getobject',
                        array('module' => 'roles'));
                    if (isset($object) && !empty($object->objectid)) {
                        // get the Dynamic Properties of this object
                        $data['properties'] = &$object->getProperties();
                    }
                }
                return $data;
            break;
        case 'notify' :
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            if (!xarVarFetch('subject', 'str:1:', $data['subject'], NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('message', 'str:1:', $data['message'], NULL, XARVAR_NOT_REQUIRED)) return;

            // Need to convert %%var%% to #$var# so that we can compile the template
            $data['message'] = preg_replace( "/%%(.+)%%/","#$\\1#", $data['message'] );
            $data['subject'] = preg_replace( "/%%(.+)%%/","#$\\1#", $data['subject'] );

            // Compile Template before sending it to senduseremail()
            $data['message'] = xarTpl::compileString($data['message']);
            $data['subject'] = xarTpl::compileString($data['subject']);

            //Send notification
            $id = unserialize(base64_decode($id));
            if (!xarMod::apiFunc('roles','admin','senduseremail', array( 'id' => $id, 'mailtype' => $data['mailtype'], 'subject' => $data['subject'], 'message' => $data['message'], 'pass' => $data['pass'], 'ip' => $data['ip']))) {
                return xarTpl::module('roles','user','errors',array('layout'=> 'mail_failed')); 
            }
            xarController::redirect(xarModURL('roles', 'admin', 'showusers',
                              array('id' => $data['groupid'], 'state' => $data['state'])));
            return true;
           break;
    }
}
?>
