<?php
/**
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * Psspl : Added API function to get the template file output for the mail message.
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['modName'] the module name<br/>
 *        string   $args['modType']      user|admin<br/>
 *        string   $args['funcName']     module function to template  <br/>
 *        string   $args['templateName'] string the specific template to call<br/>
 *        string   $args['tplData']      arguments for the template<br/>
 *        string   $args['mailtype']     The type of mail html|text
 * @return string xarTpl::file($sourceFileName, $tplData) 
 */
function mail_adminapi_mailmessagemodule(Array $args=array())
{
    extract($args);
    // Get the right source filename
    $params = array('modName' => $modName,
                    'modType' => $modType, 
                    'templateName' => $templateName, 
                    'mailType' => $mailType,
                    'messagepart' => 'message');
    $sourceFileName = xarMod::apiFunc('mail', 'admin', 'getsourcefilename', $params);
    return xarTpl::file($sourceFileName, $tplData);
}

?>