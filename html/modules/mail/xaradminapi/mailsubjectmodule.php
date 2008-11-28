<?php
/**
 * Psspl : Added API function to get the template file output for the mail subject. 
 * @access public
 * @param  string $modName      the module name
 * @param  string $modType      user|admin
 * @param  string $funcName     module function to template 
 * @param  string $templateName string the specific template to call
 * @param  array  $tplData      arguments for the template
 * @param  string $mailtype 	The type of mail html|text
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData) 
 */
function mail_adminapi_mailsubjectmodule($args)
{
	extract($args);
    // Get the right source filename
    $params = array('modName' => $modName,
					'modType' => $modType, 
					'templateName' => $templateName, 
					'mailType' => $mailType);
    $sourceFileName = xarModAPIFunc('mail', 'admin', 'getsubjectfilename', $params);    	
    return xarTpl__executeFromFile($sourceFileName, $tplData);	
}

?>



