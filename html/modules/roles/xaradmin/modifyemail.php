<?php
/**
 * Modify the  email for users
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Modify the  email for users
 */
function roles_admin_modifyemail($args)
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;

    extract($args);
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;
    if (!isset($mailtype)) xarVarFetch('mailtype', 'str:1:100', $data['mailtype'], 'welcome', XARVAR_NOT_REQUIRED);
    else $data['mailtype'] = $mailtype;

// Get the list of available templates
    $messaginghome = xarCoreGetVarDirPath() . "/messaging/roles";
    if (!file_exists($messaginghome)) throw new DirectoryNotFoundException($messaginghome);

    $dd = opendir($messaginghome);
// FIXME: what's the blank template supposed to do ?
    //$templates = array(array('key' => 'blank', 'value' => xarML('Empty')));
    $templates = array();
    while (($filename = readdir($dd)) !== false) {
        if (!is_dir($messaginghome . "/" . $filename)) {
            $pos = strpos($filename,'-message.xd');
            if (!($pos === false)) {
                $templatename = substr($filename,0,$pos);
                $templatelabel = ucfirst($templatename);
                $templates[] = array('key' => $templatename, 'value' => $templatelabel);
            }
        }
   }
    closedir($dd);
    $data['templates'] = $templates;

    switch (strtolower($phase)) {
        case 'modify':
        default:
            $strings = xarModAPIFunc('roles','admin','getmessagestrings', array('template' => $data['mailtype']));
            $data['subject'] = $strings['subject'];
            $data['message'] = $strings['message'];
            $data['authid'] = xarSecGenAuthKey();


            // dynamic properties (if any)
            $data['properties'] = null;
            if (xarModIsAvailable('dynamicdata')) {
                // get the Dynamic Object defined for this module (and itemtype, if relevant)
                // FIXME: 'Only variables should be assigned by reference' notice in php4.4
                @$object = xarModAPIFunc('dynamicdata', 'user', 'getobject',
                    array('module' => 'roles'));
                if (isset($object) && !empty($object->objectid)) {
                    // get the Dynamic Properties of this object
                    $data['properties'] = &$object->getProperties();
                }
            }
            break;

        case 'update':

            if (!xarVarFetch('message', 'str:1:', $message)) return;
            if (!xarVarFetch('subject', 'str:1:', $subject)) return;
            // Confirm authorisation code
//            if (!xarSecConfirmAuthKey()) return;
//            xarModSetVar('roles', $data['mailtype'].'email', $message);
//            xarModSetVar('roles', $data['mailtype'].'title', $subject);

            $messaginghome = xarCoreGetVarDirPath() . "/messaging/roles";
            $filebase = $messaginghome . "/" . $data['mailtype'] . "-";

            $filename = $filebase . 'subject.xd';
            if (is_writable($filename) && is_writable($messaginghome)) {
               unlink($filename);
               if (!$handle = fopen($filename, 'a')) {
                   throw new FileNotFoundException($filename,'Could not open the file "#(1)" for appending');
               }
               if (fwrite($handle, $subject) === FALSE) {
                   throw new FileNotFoundException($filename,'Could not write to the file "#(1)" for writing');
               }
               fclose($handle);
            } else {
                $msg = 'The messaging template "#(1)" is not writable or it is not allowed to delete files from #(2)';
                throw new ConfigurationException(array($filename,$messaginghome),$msg);
            }
            $filename = $filebase . 'message.xd';
            if (is_writable($filename) && is_writable($messaginghome)) {
               unlink($filename);
               if (!$handle = fopen($filename, 'a')) {
                   throw new FileNotFoundException($filename,'Could not open the file "#(1)" for appending');
               }
               if (fwrite($handle, $message) === FALSE) {
                   throw new FileNotFoundException($filename,'Could not write to the file "#(1)" for writing');
               }
               fclose($handle);
            } else {
                $msg = 'The messaging template "#(1)" is not writable or it is not allowed to delete files from #(2)';
                throw new ConfigurationException(array($filename,$messaginghome),$msg);
            }
            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyemail', array('mailtype' => $data['mailtype'])));
            return true;
            break;
    }
    return $data;
}

?>
