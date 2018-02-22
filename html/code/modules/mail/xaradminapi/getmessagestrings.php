<?php
/**
 * Get message
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
 *        string   $args['template'] name of the email type which has apair of -subject and -message files<br/>
 *        string   $args['module'] module directory in var/messaging
 * @return array of strings of file contents read
 */
function mail_adminapi_getmessagestrings(Array $args=array())
{
    extract($args);
    if (!isset($template)) throw new EmptyParameterException('template');

    if(!isset($module)){
        list($module) = xarController::$request->getInfo();
    }

    $messaginghome = sys::varpath() . "/messaging/" . $module;
    $subjtemplate = $messaginghome . "/" . $template . "-subject.xt";
    if (!file_exists($subjtemplate)) throw new FileNotFoundException($subjtemplate);
    $string = '';
    $fd = fopen($subjtemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $subject = $string;
    fclose($fd);

    $msgtemplate = $messaginghome . "/" . $template . "-message.xt";
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
