<?php
/**
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * Modify the  email for users
 * @return array data for the template display
 */
function roles_admin_modifyemail(Array $args=array())
{
    // Security
    if (!xarSecurityCheck('EditRoles')) return;

    extract($args);
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;
    if (!isset($mailtype)) xarVarFetch('mailtype', 'str:1:100', $data['mailtype'], 'welcome', XARVAR_NOT_REQUIRED);
    else $data['mailtype'] = $mailtype;

    // Get the list of available templates
    $messaginghome = sys::varpath() . "/messaging/roles";
    if (!file_exists($messaginghome)) throw new DirectoryNotFoundException($messaginghome);

    $dd = opendir($messaginghome);
    // FIXME: what's the blank template supposed to do ?
    //$templates = array(array('key' => 'blank', 'value' => xarML('Empty')));
    $templates = array();
    while (($filename = readdir($dd)) !== false) {
        if (!is_dir($messaginghome . "/" . $filename)) {
            $pos = strpos($filename,'-message.xt');
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
            $strings = xarMod::apiFunc('roles','admin','getmessagestrings', array('template' => $data['mailtype']));
            $data['subject'] = $strings['subject'];
            $data['message'] = $strings['message'];
            $data['authid'] = xarSecGenAuthKey();

            $object = DataObjectMaster::getObject(array('name' => 'roles_users'));
                if (isset($object) && !empty($object->objectid)) {
                    // get the Dynamic Properties of this object
                    $data['properties'] = &$object->getProperties();
                }
            break;

        case 'update':

            if (!xarVarFetch('message', 'str:1:', $message)) return;
            if (!xarVarFetch('subject', 'str:1:', $subject)) return;
            // Confirm authorisation code
//            if (!xarSecConfirmAuthKey()) return;
//            xarModVars::set('roles', $data['mailtype'].'email', $message);
//            xarModVars::set('roles', $data['mailtype'].'title', $subject);

            $messaginghome = sys::varpath() . "/messaging/roles";
            $filebase = $messaginghome . "/" . $data['mailtype'] . "-";

            $filename = $filebase . 'subject.xt';
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
            $filename = $filebase . 'message.xt';
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
            xarController::redirect(xarModURL('roles', 'admin', 'modifyemail', array('mailtype' => $data['mailtype'])));
            return true;
            break;
    }
    return $data;
}
?>
