<?php
/**
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
 * Psspl : Added API function to get the template file output for the mail subject. 
 * @access public
 * @param  string $modName      the module name
 * @param  string $modType      user|admin
 * @param  string $funcName     module function to template 
 * @param  string $templateName string the specific template to call
 * @param  array  $tplData      arguments for the template
 * @param  string $mailtype     The type of mail html|text
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData) 
 */
function mail_adminapi_mailsubjectmodule(Array $args=array())
{
    extract($args);
    // Get the right source filename
    $params = array('modName' => $modName,
                    'modType' => $modType, 
                    'templateName' => $templateName, 
                    'mailType' => $mailType,
                    'messagepart' => 'subject');
    $sourceFileName = xarMod::apiFunc('mail', 'admin', 'getsourcefilename', $params);     
    return xarTpl__executeFromFile($sourceFileName, $tplData);  
}

?>