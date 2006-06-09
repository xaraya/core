<?php
/**
 * Get message include string
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['template'] name of the template without .xd extension
 * @param $args['module'] module directory in var/messaging
 * @return string of file contents read
 */
function roles_adminapi_getmessageincludestring($args)
{
    extract($args);
    if (!isset($template)) throw new EmptyParameterException('template');

    if(!isset($module)){
        list($module) = xarRequestGetInfo();
    }

// Get the template that defines the substitution vars
    $messaginghome = xarCoreGetVarDirPath() . "/messaging/" . $module;
    $msgtemplate = $messaginghome . "/includes/" . $template . ".xd";
    if (!file_exists($msgtemplate)) throw new FileNotFoundException($msgtemplate);

    $string = '';
    $fd = fopen($msgtemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    fclose($fd);
    return $string;
}

?>
