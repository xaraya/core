<?php
/**
 * Psspl : Added API function to determine the template mail subject sourcefile to use
 * Based on the module, the basename for the template
 * a possible overribe determine the template mail source we should use and loads
 * the appropriate translations based on the outcome.
 *
 * @param  string $modName      Module name doing the request * 							    
 * @param  string $modtype      The base name for the template user|admin
 * @param  string $templateName The name for the template to use if any
 * @param  string $mailtype 	The type of mail html|text 
 * @return string
 *
 * @todo do we need to load the translations here or a bit later? (here:easy, later: better abstraction)
 */
function mail_adminapi_getsubjectfilename($args)
{
	extract($args);  
	
	// Template search order:
    // 1. var/messaging/{module}/{type}-{template Name}-{message type}-subject.xd
    // 2. var/messaging/{module}/{type}-{template Name}-text-subject.xd
    // 3. var/messaging/{template Name}-{message type}-subject.xd
    // 4. var/messaging/{template Name}-text-subject.xd
    // 5. complain (later on)
   
    $tplMessagingDir = sys::varpath() . "/messaging/$modName";    
    if (!file_exists($tplMessagingDir)) 
    throw new DirectoryNotFoundException($tplMessagingDir);
    
    unset($sourceFileName);

    xarLogMessage("TPL: 1. $tplMessagingDir/$modType-$templateName-$mailType-subject.xd");
    xarLogMessage("TPL: 2. $tplMessagingDir/$modType-$templateName-text-subject.xd");
       
    if(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$modType-$templateName-$mailType-subject.xd")) {
        
    } elseif(!empty($templateName) &&
        file_exists($sourceFileName = "$tplMessagingDir/$modType-$templateName-text-subject.xd")) { 

    } elseif(!empty($templateName) &&
    	file_exists($sourceFileName = "$tplMessagingDir/$templateName-$mailType-subject.xd")) { 
    		
    } elseif(!empty($templateName) &&
    	file_exists($sourceFileName = "$tplMessagingDir/$templateName-text-subject.xd")) {
        	
    } else{
    	throw new FileNotFoundException($sourceFileName);
    }
    $sourceFileName = str_replace('//','/',$sourceFileName);
    // assert('isset($sourceFileName); /* The source file for the template has no value in xarTplModule */');
    return $sourceFileName;
}

?>



