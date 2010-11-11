<?php
/**
 * Update message
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param array   $args array of parameters
 * @param $args['module'] module directory in var/messaging
 * @param $args['template'] name of the email type which has apair of -subject and -message files
 * @param $args['subject'] new subject
 * @param $args['message'] new message
 * @return array of strings of file contents read
 */
function mail_adminapi_updatemessagestrings(Array $args=array())
{
    extract($args);
    if (empty($template)) throw new EmptyParameterException('template');

    if (empty($module)) {
        list($module) = xarController::$request->getInfo();
    }
    if (empty($subject)) {
        $subject = '';
    }
    if (empty($message)) {
        $message = '';
    }

    $messaginghome = sys::varpath() . '/messaging/' . $module;
    if (!file_exists($messaginghome)) {
        throw new DirectoryNotFoundException($messaginghome);
    }

    $filename = $messaginghome . '/' . $template . '-subject.xt';
    if (is_writable($filename)) {
        unlink($filename);
        if (!$handle = fopen($filename, 'a')) {
            throw new FileNotFoundException($filename,'Can not find or can not open the file: #(1)');
        }
        if (fwrite($handle, $subject) === FALSE) {
            throw new FileNotFoundException($filename,'Can not find or can not write to the file: #(1)');
        }
        fclose($handle);
    }

    $filename = $messaginghome . '/' . $template . '-message.xt';
    if (is_writable($filename)) {
        unlink($filename);
        if (!$handle = fopen($filename, 'a')) {
            throw new FileNotFoundException($filename,'Can not find or can not open the file: #(1)');
        }
        if (fwrite($handle, $message) === FALSE) {
            throw new FileNotFoundException($filename,'Can not find or can not write to the file: #(1)');
        }
        fclose($handle);
    }

    return true;
}

?>
