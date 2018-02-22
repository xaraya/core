<?php
/**
 * Get message templates
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        string   $args['module'] module directory in var/messaging
 * @return array of template names and labels
 */
function mail_adminapi_getmessagetemplates(Array $args=array())
{
    extract($args);

    if (empty($module)) {
        list($module) = xarController::$request->getInfo();
    }

    $messaginghome = sys::varpath() . "/messaging/" . $module;
    if (!file_exists($messaginghome)) throw new DirectoryNotFoundException($messaginghome);

    $dd = opendir($messaginghome);
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

    return $templates;
}

?>
