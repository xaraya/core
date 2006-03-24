<?php
/**
 * Update message
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param $args['module'] module directory in var/messaging
 * @param $args['template'] name of the email type which has apair of -subject and -message files
 * @param $args['subject'] new subject
 * @param $args['message'] new message
 * @return array of strings of file contents read
 */
function mail_adminapi_updatemessagestrings($args)
{
    extract($args);
    if (empty($template)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_DATA', new SystemException('No template name was given.'));
        return;
    }
    if (empty($module)) {
        list($module) = xarRequestGetInfo();
    }
    if (empty($subject)) {
        $subject = '';
    }
    if (empty($message)) {
        $message = '';
    }

    $messaginghome = xarCoreGetVarDirPath() . '/messaging/' . $module;
    if (!file_exists($messaginghome)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The messaging directory was not found.'));
        return;
    }

    $filename = $messaginghome . '/' . $template . '-subject.xd';
    if (is_writable($filename)) {
        unlink($filename);
        if (!$handle = fopen($filename, 'a')) {
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('Cannot open the template.'));
            return;
        }
        if (fwrite($handle, $subject) === FALSE) {
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('Cannot write the template.'));
            return;
        }
        fclose($handle);
    }

    $filename = $messaginghome . '/' . $template . '-message.xd';
    if (is_writable($filename)) {
        unlink($filename);
        if (!$handle = fopen($filename, 'a')) {
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('Cannot open the template.'));
            return;
        }
        if (fwrite($handle, $message) === FALSE) {
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('Cannot write the template.'));
            return;
        }
        fclose($handle);
    }

    return true;
}

?>
