<?php
/**
 * Get message
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['template'] name of the email type which has apair of -subject and -message files
 * @param $args['module'] module directory in var/messaging
 * @return array of strings of file contents read
 */
function roles_adminapi_getmessagestrings($args)
{
    extract($args);
    if (!isset($template)) throw new EmptyParameterException('template');

    //FIXME: the default is always roles
    if(!isset($module)){
        list($module) = xarRequestGetInfo();
    }

    $messaginghome = sys::varpath() . "/messaging/" . $module;
    $subjtemplate = $messaginghome . "/" . $template . "-subject.xd";
    if (!file_exists($subjtemplate)) throw new FileNotFoundException($subjtemplate);

    $string = '';
    $fd = fopen($subjtemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $subject = $string;
    fclose($fd);

    $msgtemplate = $messaginghome . "/" . $template . "-message.xd";
    if (!file_exists($msgtemplate)) throw new FileNotFoundException($msgtemplate);

    $string = '';
    $fd = fopen($msgtemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $message = $string;
    fclose($fd);

    return array('subject' => $subject, 'message' => $message);
}

?>
