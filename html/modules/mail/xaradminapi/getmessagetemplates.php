<?php
/**
 * Get message templates
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 */

/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['module'] module directory in var/messaging
 * @return array of template names and labels
 */
function mail_adminapi_getmessagetemplates($args)
{
    extract($args);

    if (empty($module)) {
        list($module) = xarRequestGetInfo();
    }

    $messaginghome = xarCoreGetVarDirPath() . "/messaging/" . $module;
    if (!file_exists($messaginghome)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The messaging directory was not found.'));
        return;
    }
    $dd = opendir($messaginghome);
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

    return $templates;
}

?>
