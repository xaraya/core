<?php
/**
 * Get message
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Ryan Walker <ryandw@gmail.com>
 * @param $args['template'] name of the email type which has apair of -subject and -message files
 * @param $args['module'] module directory in var/messaging
 * @param $args['tpl_path'] string override the template location (optional)
 * @return array of strings of file contents read
 */
function roles_adminapi_getmessagestrings($args)
{
    extract($args);
    if (!isset($template)) throw new EmptyParameterException('template');

    if(!isset($module)){
        list($module) = xarRequest::getInfo();
    }

	if (!isset($tpl_path)) {
		$tpl_path = sys::varpath() . "/messaging/" . $module;
	}

    $subjtemplate = $tpl_path . "/" . $template . "-subject.xt";
	if (file_exists($subjtemplate)) {		
		$string = '';
		$fd = fopen($subjtemplate, 'r');
		while(!feof($fd)) {
			$line = fgets($fd, 1024);
			$string .= $line;
		}
		$subject = $string;
		fclose($fd);
	} else {
		$subject = '';
	}
	
    $msgtemplate = $tpl_path . "/" . $template . "-message.xt";
	if (file_exists($msgtemplate)) {
		$string = '';
		$fd = fopen($msgtemplate, 'r');
		while(!feof($fd)) {
			$line = fgets($fd, 1024);
			$string .= $line;
		}
		$message = $string;
		fclose($fd);
	} else {
		$message = '';
	}

    return array('subject' => $subject, 'message' => $message);
}

?>