<?php
/**
 * Psspl : Added API function to get the template file output for the mail message.
 * @access public
 * @param  string $modName      the module name
 * @param  string $modType      user|admin
 * @param  string $funcName     module function to template  
 * @param  string $templateName string the specific template to call
 * @param  array  $tplData      arguments for the template
 * @param  string $mailtype     The type of mail html|text
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData) 
 */
function mail_adminapi_mailmessagemodule($args)
{
    extract($args);
    // Get the right source filename
    $params = array('modName' => $modName,
                    'modType' => $modType, 
                    'templateName' => $templateName, 
                    'mailType' => $mailType,
                    'messagepart' => 'message');
    $sourceFileName = xarModAPIFunc('mail', 'admin', 'getsourcefilename', $params);
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

?>



