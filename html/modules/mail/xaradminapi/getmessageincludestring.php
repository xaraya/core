<?php
/**
 * Return message 
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param $args['template'] name of the template without .xd extension
 * @param $args['module'] module directory in var/messaging
 * @return string of file contents read
 */
function mail_adminapi_getmessageincludestring($args)
{
    extract($args);
    if (!isset($template)) throw new EmptyParameterException('template');

    if(!isset($module)){
        list($module) = xarRequestGetInfo();
    }

    // Get the template that defines the substitution vars
    $messaginghome = sys::varpath() . "/messaging/" . $module;
    $vartemplate = $messaginghome . "/includes/" . $template . ".xd";
    if (!file_exists($vartemplate)) throw new FileNotFoundException($vartemplate);
   
    $string = '';
    $fd = fopen($vartemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    fclose($fd);
    return $string;
}

?>
